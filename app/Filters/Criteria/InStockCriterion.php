<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use App\Models\Product;
use App\Utilities\InputConverter;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Product
 *
 * @implements Criterion<TModel>
 */
final readonly class InStockCriterion implements Criterion
{
    public function __construct(private InputConverter $inputConverter = new InputConverter())
    {
    }

    public function canApply(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * @param  Builder<Product>  $builder
     * @return Builder<Product>
     */
    public function apply(Builder $builder, mixed $value): Builder
    {
        $inStock = $this->inputConverter->toBoolean($value);

        return $builder->where(function (Builder $query) use ($inStock): void {
            $this->applySimpleProductCondition($query, $inStock);
            $this->applyVariantCondition($query, $inStock);
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySimpleProductCondition(Builder $query, bool $inStock): void
    {
        $query->where(fn (Builder $q): Builder => $q->whereDoesntHave('variants')->where('in_stock', $inStock));
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyVariantCondition(Builder $query, bool $inStock): void
    {
        $query->orWhereHas('variants', fn (Builder $q): Builder => $q->where('in_stock', $inStock));
    }
}
