<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Enums\ReviewStatus;
use App\Filters\Contracts\Criterion;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Product
 *
 * @implements Criterion<TModel>
 */
final readonly class RatingCriterion implements Criterion
{
    public function canApply(mixed $value): bool
    {
        return is_numeric($value) && (float) $value > 0;
    }

    /**
     * @param  Builder<Product>  $builder
     * @return Builder<Product>
     */
    public function apply(Builder $builder, mixed $value): Builder
    {
        return $builder->whereRaw(
            '(select coalesce(avg(rating), 0) from reviews where reviews.product_id = products.id and reviews.status = ?) >= cast(? as decimal(3,2))',
            [ReviewStatus::Approved->value, (string) (float) $value],
        );
    }
}
