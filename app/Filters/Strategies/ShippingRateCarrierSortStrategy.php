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
final readonly class ShippingRateCarrierSortStrategy implements ColumnSortStrategy
{
    public function apply(Builder $builder, string $direction): Builder
    {
        return $builder
            ->leftJoin('shipping_carriers', 'shipping_rates.shipping_carrier_id', '=', 'shipping_carriers.id')
            ->orderByRaw(...TranslatableJsonExtract::orderByExpression('shipping_carriers.name', $direction))
            ->select('shipping_rates.*');
    }
}
