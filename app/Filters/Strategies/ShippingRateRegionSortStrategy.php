<?php

declare(strict_types=1);

namespace App\Filters\Strategies;

use App\Filters\Contracts\ColumnSortStrategy;
use App\Utilities\TranslatableJsonExtract;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\ShippingRate
 *
 * @implements ColumnSortStrategy<TModel>
 */
final readonly class ShippingRateRegionSortStrategy implements ColumnSortStrategy
{
    public function apply(Builder $builder, string $direction): Builder
    {
        return $builder
            ->leftJoin('regions', 'shipping_rates.region_id', '=', 'regions.id')
            ->orderByRaw(...TranslatableJsonExtract::orderByExpression('regions.name', $direction))
            ->select('shipping_rates.*');
    }
}
