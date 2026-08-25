<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A product matches when any of its purchasable prices falls inside the range:
 * the product price for simple products, any variant price otherwise.
 *
 * @template TModel of Product
 *
 * @implements Criterion<TModel>
 */
final readonly class ProductPriceRangeCriterion implements Criterion
{
    public function canApply(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        return is_numeric($value['min'] ?? null) || is_numeric($value['max'] ?? null);
    }

    /**
     * @param  Builder<Product>  $builder
     * @return Builder<Product>
     */
    public function apply(Builder $builder, mixed $value): Builder
    {
        $min = is_numeric($value['min'] ?? null) ? $value['min'] : null;
        $max = is_numeric($value['max'] ?? null) ? $value['max'] : null;

        return $builder->where(function (Builder $query) use ($min, $max): void {
            $query
                ->where(fn (Builder $simple): Builder => $this->applyBounds(
                    $simple->whereDoesntHave('variants'),
                    'price',
                    $min,
                    $max,
                ))
                ->orWhereHas('variants', fn (Builder $variants): Builder => $this->applyBounds($variants, 'price', $min, $max));
        });
    }

    /**
     * @template TQuery of Model
     *
     * @param  Builder<TQuery>  $query
     * @return Builder<TQuery>
     */
    private function applyBounds(Builder $query, string $column, mixed $min, mixed $max): Builder
    {
        if ($min !== null) {
            $query->where($column, '>=', $min);
        }

        if ($max !== null) {
            $query->where($column, '<=', $max);
        }

        return $query;
    }
}
