<?php

declare(strict_types=1);

namespace App\Filters\Strategies;

use App\Filters\Contracts\ColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements ColumnSortStrategy<TModel>
 */
final readonly class ProductsCountColumnSortStrategy implements ColumnSortStrategy
{
    /**
     * @param  'asc'|'desc'  $direction
     */
    public function apply(Builder $builder, string $direction): Builder
    {
        return $builder->withCount('products')->orderBy('products_count', $direction);
    }
}
