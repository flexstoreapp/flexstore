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
final readonly class PriceColumnSortStrategy implements ColumnSortStrategy
{
    public function apply(Builder $builder, string $direction): Builder
    {
        return $builder
            ->leftJoinSub(
                DB::table('product_variants')
                    ->select('product_id', DB::raw('MIN(price) as min_variant_price'))
                    ->groupBy('product_id'),
                'variant_prices',
                'products.id',
                '=',
                'variant_prices.product_id'
            )
            ->orderByRaw(
                match ($direction) {
                    'desc' => 'COALESCE(NULLIF(products.price, 0), variant_prices.min_variant_price) desc',
                    default => 'COALESCE(NULLIF(products.price, 0), variant_prices.min_variant_price) asc',
                }
            );
    }
}
