<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use App\Models\Product;
use App\Models\Setting;
use App\Utilities\InputConverter;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Product
 *
 * @implements Criterion<TModel>
 */
final readonly class LowStockCriterion implements Criterion
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
        $booleanValue = $this->inputConverter->toBoolean($value);

        if (! $booleanValue) {
            return $builder;
        }

        $defaultThreshold = Setting::getValue('default_low_stock_threshold', 10);

        return $builder->where('track_stock', true)
            ->whereNotNull('stock')
            ->where(function (Builder $query) use ($defaultThreshold): void {
                $query->where(function (Builder $q): void {
                    $q->whereNotNull('low_stock_threshold')
                        ->whereColumn('stock', '<=', 'low_stock_threshold');
                })
                    ->orWhere(function (Builder $q) use ($defaultThreshold): void {
                        $q->whereNull('low_stock_threshold')
                            ->where('stock', '<=', $defaultThreshold);
                    });
            });
    }
}
