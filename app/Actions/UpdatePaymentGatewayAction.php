<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdatePaymentGatewayInput;
use App\Models\PaymentGateway;

final readonly class UpdatePaymentGatewayAction
{
    public function handle(PaymentGateway $paymentGateway, UpdatePaymentGatewayInput $input): PaymentGateway
    {
        $paymentGateway->update([
            'name' => $input->has('name') ? $input->name : $paymentGateway->name,
            'driver' => $input->has('driver') ? $input->driver : $paymentGateway->driver,
            'credentials' => $input->has('credentials') ? $input->credentials : $paymentGateway->credentials,
            'min_order_value' => $input->has('min_order_value') ? $input->minOrderValue : $paymentGateway->min_order_value,
            'max_order_value' => $input->has('max_order_value') ? $input->maxOrderValue : $paymentGateway->max_order_value,
            'min_weight' => $input->has('min_weight') ? $input->minWeight : $paymentGateway->min_weight,
            'min_weight_unit' => $input->has('min_weight_unit') ? $input->minWeightUnit : $paymentGateway->min_weight_unit,
            'max_weight' => $input->has('max_weight') ? $input->maxWeight : $paymentGateway->max_weight,
            'max_weight_unit' => $input->has('max_weight_unit') ? $input->maxWeightUnit : $paymentGateway->max_weight_unit,
            'excluded_products' => $input->has('excluded_products') ? $input->excludedProducts : $paymentGateway->excluded_products,
            'excluded_categories' => $input->has('excluded_categories') ? $input->excludedCategories : $paymentGateway->excluded_categories,
            'excluded_brands' => $input->has('excluded_brands') ? $input->excludedBrands : $paymentGateway->excluded_brands,
            'allowed_regions' => $input->has('allowed_regions') ? $input->allowedRegions : $paymentGateway->allowed_regions,
            'supported_currencies' => $input->has('supported_currencies') ? $input->supportedCurrencies : $paymentGateway->supported_currencies,
            'sync_external_refunds' => $input->has('sync_external_refunds') ? $input->syncExternalRefunds : $paymentGateway->sync_external_refunds,
            'is_active' => $input->has('is_active') ? $input->isActive : $paymentGateway->is_active,
        ]);

        return $paymentGateway;
    }
}
