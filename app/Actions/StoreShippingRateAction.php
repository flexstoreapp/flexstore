<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreShippingRateInput;
use App\Models\ShippingRate;

final readonly class StoreShippingRateAction
{
    public function handle(StoreShippingRateInput $input): ShippingRate
    {
        return ShippingRate::query()->create([
            'region_id' => $input->regionId,
            'shipping_carrier_id' => $input->shippingCarrierId,
            'name' => $input->name,
            'type' => $input->type,
            'rate' => $input->rate,
            'delivery_time' => $input->deliveryTime,
            'min_order_value' => $input->minOrderValue,
            'max_order_value' => $input->maxOrderValue,
            'min_weight' => $input->minWeight,
            'min_weight_unit' => $input->minWeightUnit,
            'max_weight' => $input->maxWeight,
            'max_weight_unit' => $input->maxWeightUnit,
            'excluded_products' => $input->excludedProducts,
            'excluded_categories' => $input->excludedCategories,
            'excluded_brands' => $input->excludedBrands,
            'is_active' => $input->isActive,
        ]);
    }
}
