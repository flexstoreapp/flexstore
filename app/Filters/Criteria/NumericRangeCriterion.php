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
final readonly class NumericRangeCriterion implements Criterion
{
    public const string TYPE_MIN = 'min';

    public const string TYPE_MAX = 'max';

    /**
     * @param  'min'|'max'  $type
     */
    public function __construct(
        private string $column,
        private string $type
    ) {
    }

    public function canApply(mixed $value): bool
    {
        return is_numeric($value);
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        $operator = $this->type === self::TYPE_MIN ? '>=' : '<=';

        return $builder->where($this->column, $operator, $value);
    }
}
