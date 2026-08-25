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
final readonly class RelationshipCriterion implements Criterion
{
    public function __construct(
        private string $relationship,
        private string $column,
        private string $operator = '='
    ) {
    }

    public function canApply(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        return $builder->whereRelation($this->relationship, $this->column, $this->operator, $value);
    }
}
