<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use App\Utilities\InputConverter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements Criterion<TModel>
 */
final readonly class RelationshipExistsCriterion implements Criterion
{
    public function __construct(
        private string $relationship,
        private InputConverter $inputConverter = new InputConverter()
    ) {
    }

    public function canApply(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        $hasRelationship = $this->inputConverter->toBoolean($value);

        return $hasRelationship
            ? $builder->has($this->relationship)
            : $builder->doesntHave($this->relationship);
    }
}
