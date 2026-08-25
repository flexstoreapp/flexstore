<?php

declare(strict_types=1);

namespace App\Filters\Strategies;

use App\Filters\Contracts\ColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of \App\Models\Product
 *
 * @implements ColumnSortStrategy<TModel>
 */
final readonly class StockColumnSortStrategy implements ColumnSortStrategy
{
    public function apply(Builder $builder, string $direction): Builder
    {
        return $builder
            ->leftJoinSub(
                DB::table('product_variants')
                    ->select('product_id', DB::raw('COALESCE(SUM(stock), 0) as total_variant_quantity'))
                    ->groupBy('product_id'),
                'variant_quantities',
                'products.id',
                '=',
                'variant_quantities.product_id'
            )
            ->orderByRaw(
                match ($direction) {
                    'desc' => 'COALESCE(NULLIF(products.stock, 0), variant_quantities.total_variant_quantity, 0) desc',
                    default => 'COALESCE(NULLIF(products.stock, 0), variant_quantities.total_variant_quantity, 0) asc',
                }
            );
    }
}
