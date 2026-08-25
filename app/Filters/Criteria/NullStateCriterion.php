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
final readonly class NullStateCriterion implements Criterion
{
    public function __construct(
        private string $column,
        private string $nullValue,
        private string $notNullValue,
    ) {
    }

    public function canApply(mixed $value): bool
    {
        return $value === $this->nullValue || $value === $this->notNullValue;
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        return $value === $this->nullValue
            ? $builder->whereNull($this->column)
            : $builder->whereNotNull($this->column);
    }
}
