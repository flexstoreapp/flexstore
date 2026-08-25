<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\ColumnSortStrategy;
use App\Filters\Contracts\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of Model
 *
 * @implements Criterion<TModel>
 */
final readonly class SortCriterion implements Criterion
{
    /**
     * @param  array<string>  $allowedColumns
     * @param  array<string, ColumnSortStrategy<TModel>>  $columnStrategies
     * @param  'asc'|'desc'  $defaultDirection
     */
    public function __construct(
        private array $allowedColumns,
        private string $defaultColumn = 'created_at',
        private array $columnStrategies = [],
        private mixed $direction = null,
        private string $defaultDirection = 'desc',
    ) {
    }

    public function canApply(mixed $value): bool
    {
        return true;
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        $column = $this->getValidColumn($value);
        $direction = $this->getValidDirection();

        if (isset($this->columnStrategies[$column])) {
            return $this->columnStrategies[$column]->apply($builder, $direction);
        }

        $needsExplicitNullHandling = match (DB::getDriverName()) {
            'pgsql' => true,
            'sqlite' => $direction === 'desc',
            default => false,
        };

        if ($needsExplicitNullHandling) {
            $nulls = $direction === 'asc' ? 'first' : 'last';
            $builder = $builder->orderByRaw("{$column} {$direction} nulls {$nulls}"); // @phpstan-ignore argument.type
        } else {
            $builder = $builder->orderBy($column, $direction);
        }

        return $column === 'id' ? $builder : $builder->orderBy('id');
    }

    private function getValidColumn(mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->defaultColumn;
        }

        return in_array($value, $this->allowedColumns, true)
            ? $value
            : $this->defaultColumn;
    }

    /**
     * @return 'asc'|'desc'
     */
    private function getValidDirection(): string
    {
        $direction = mb_strtolower((string) ($this->direction ?? $this->defaultDirection));

        return in_array($direction, ['asc', 'desc'], true)
            ? $direction
            : $this->defaultDirection;
    }
}
