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
final readonly class RoleCriterion implements Criterion
{
    public function canApply(mixed $value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        return $builder->whereRelation('roles', 'id', $value);
    }
}
