<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StorePaymentGatewayInput;
use App\Models\PaymentGateway;

final readonly class StorePaymentGatewayAction
{
    public function handle(StorePaymentGatewayInput $input): PaymentGateway
    {
        return PaymentGateway::query()->create([
            'name' => $input->name,
            'driver' => $input->driver,
            'credentials' => $input->credentials,
            'min_order_value' => $input->minOrderValue,
            'max_order_value' => $input->maxOrderValue,
            'min_weight' => $input->minWeight,
            'min_weight_unit' => $input->minWeightUnit,
            'max_weight' => $input->maxWeight,
            'max_weight_unit' => $input->maxWeightUnit,
            'excluded_products' => $input->excludedProducts,
            'excluded_categories' => $input->excludedCategories,
            'excluded_brands' => $input->excludedBrands,
            'allowed_regions' => $input->allowedRegions,
            'supported_currencies' => $input->supportedCurrencies,
            'sync_external_refunds' => $input->syncExternalRefunds,
            'is_active' => $input->isActive,
        ]);
    }
}
