<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements Criterion<TModel>
 */
final readonly class CategoryWithDescendantsCriterion implements Criterion
{
    public function canApply(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, mixed $value): Builder
    {
        $categoryIds = Category::query()->withDescendants((int) $value)->pluck('id');

        if ($categoryIds->isEmpty()) {
            return $builder->whereRaw('1 = 0');
        }

        return $builder->whereIn('category_id', $categoryIds);
    }
}
