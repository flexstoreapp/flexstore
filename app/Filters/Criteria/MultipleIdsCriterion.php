<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements Criterion<TModel>
 */
final readonly class MultipleIdsCriterion implements Criterion
{
    public function __construct(
        private string $column,
    ) {
    }

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
        $ids = is_array($value)
            ? $value
            : array_filter(array_map(intval(...), explode(',', (string) $value)));

        if (count($ids) === 0) {
            return $builder;
        }

        if (count($ids) === 1) {
            return $builder->where($this->column, $ids[0]);
        }

        return $builder->whereIn($this->column, $ids);
    }
}
