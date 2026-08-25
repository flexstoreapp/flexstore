<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateShippingRateInput;
use App\Models\ShippingRate;

final readonly class UpdateShippingRateAction
{
    public function handle(ShippingRate $shippingRate, UpdateShippingRateInput $input): ShippingRate
    {
        $shippingRate->update([
            'region_id' => $input->has('region_id') ? $input->regionId : $shippingRate->region_id,
            'shipping_carrier_id' => $input->has('shipping_carrier_id') ? $input->shippingCarrierId : $shippingRate->shipping_carrier_id,
            'name' => $input->has('name') ? $input->name : $shippingRate->name,
            'type' => $input->has('type') ? $input->type : $shippingRate->type,
            'rate' => $input->has('rate') ? $input->rate : $shippingRate->rate,
            'delivery_time' => $input->has('delivery_time') ? $input->deliveryTime : $shippingRate->delivery_time,
            'min_order_value' => $input->has('min_order_value') ? $input->minOrderValue : $shippingRate->min_order_value,
            'max_order_value' => $input->has('max_order_value') ? $input->maxOrderValue : $shippingRate->max_order_value,
            'min_weight' => $input->has('min_weight') ? $input->minWeight : $shippingRate->min_weight,
            'min_weight_unit' => $input->has('min_weight_unit') ? $input->minWeightUnit : $shippingRate->min_weight_unit,
            'max_weight' => $input->has('max_weight') ? $input->maxWeight : $shippingRate->max_weight,
            'max_weight_unit' => $input->has('max_weight_unit') ? $input->maxWeightUnit : $shippingRate->max_weight_unit,
            'excluded_products' => $input->has('excluded_products') ? $input->excludedProducts : $shippingRate->excluded_products,
            'excluded_categories' => $input->has('excluded_categories') ? $input->excludedCategories : $shippingRate->excluded_categories,
            'excluded_brands' => $input->has('excluded_brands') ? $input->excludedBrands : $shippingRate->excluded_brands,
            'is_active' => $input->has('is_active') ? $input->isActive : $shippingRate->is_active,
        ]);

        return $shippingRate;
    }
}
