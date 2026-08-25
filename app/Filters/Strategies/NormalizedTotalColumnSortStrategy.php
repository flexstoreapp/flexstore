<?php

declare(strict_types=1);

namespace App\Filters\Strategies;

use App\Filters\Contracts\ColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Order
 *
 * @implements ColumnSortStrategy<TModel>
 */
final readonly class NormalizedTotalColumnSortStrategy implements ColumnSortStrategy
{
    public function apply(Builder $builder, string $direction): Builder
    {
        return $builder->orderByRaw(
            match ($direction) {
                'desc' => 'total / exchange_rate desc',
                default => 'total / exchange_rate asc',
            }
        );
    }
}
