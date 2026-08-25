<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @template TModel of Model
 *
 * @implements Criterion<TModel>
 */
final readonly class MultipleCategoriesWithDescendantsCriterion implements Criterion
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
        $selectedIds = is_array($value)
            ? array_map(intval(...), $value)
            : array_filter(array_map(intval(...), explode(',', (string) $value)));

        if ($selectedIds === []) {
            return $builder;
        }

        $categoryIds = collect($selectedIds)
            ->flatMap(fn (int $id): Collection => Category::query()->withDescendants($id)->pluck('id'))
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return $builder->whereRaw('1 = 0');
        }

        return $builder->whereIn('category_id', $categoryIds);
    }
}
