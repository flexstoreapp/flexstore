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
final readonly class OnSaleCriterion implements Criterion
{
    public function __construct(private InputConverter $inputConverter = new InputConverter())
    {
    }

    public function canApply(mixed $value): bool
    {
        return $value !== null && $value !== '' && $this->inputConverter->toBoolean($value);
    }

    /**
     * @param  Builder<Product>  $builder
     * @return Builder<Product>
     */
    public function apply(Builder $builder, mixed $value): Builder
    {
        return $builder
            ->whereNotNull('compare_at_price')
            ->whereColumn('compare_at_price', '>', 'price');
    }
}
