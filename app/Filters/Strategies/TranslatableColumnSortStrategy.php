<?php

declare(strict_types=1);

namespace App\Filters\Strategies;

use App\Filters\Contracts\ColumnSortStrategy;
use App\Utilities\TranslatableJsonExtract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements ColumnSortStrategy<TModel>
 */
final readonly class TranslatableColumnSortStrategy implements ColumnSortStrategy
{
    /**
     * @param  literal-string  $column
     */
    public function __construct(private string $column)
    {
    }

    public function apply(Builder $builder, string $direction): Builder
    {
        return $builder->orderByRaw(...TranslatableJsonExtract::orderByExpression($this->column, $direction));
    }
}
