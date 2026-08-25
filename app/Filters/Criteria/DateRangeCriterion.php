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
final readonly class DateRangeCriterion implements Criterion
{
    public const string TYPE_BEFORE = 'before';

    public const string TYPE_AFTER = 'after';

    /**
     * @param  'before'|'after'  $type
     */
    public function __construct(
        private string $column,
        private string $type,
    ) {
    }

    public function canApply(mixed $value): bool
    {
        return is_string($value) && mb_trim($value) !== '';
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        $operator = $this->type === self::TYPE_BEFORE ? '<=' : '>=';

        return $builder->whereDate($this->column, $operator, $value);
    }
}
