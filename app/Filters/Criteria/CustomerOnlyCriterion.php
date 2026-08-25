<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Enums\Role as RoleEnum;
use App\Filters\Contracts\Criterion;
use App\Utilities\InputConverter;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\User
 *
 * @implements Criterion<TModel>
 */
final readonly class CustomerOnlyCriterion implements Criterion
{
    public function __construct(private InputConverter $inputConverter = new InputConverter())
    {
    }

    public function canApply(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        $booleanValue = $this->inputConverter->toBoolean($value);

        if (! $booleanValue) {
            return $builder;
        }

        return $builder->whereRelation('roles', 'name', RoleEnum::Customer);
    }
}
