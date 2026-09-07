<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\ShippingRateType;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps a single option from EligibleShippingOptionsQuery or CheckoutShippingOptionsQuery.
 */
final class ShippingOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $option */
        $option = $this->resource;
        $type = $option['type'];

        return [
            'id' => $option['id'],
            'rate_id' => $option['rate_id'] ?? $option['id'],
            'quote_reference' => $option['quote_reference'] ?? null,
            'service_code' => $option['service_code'] ?? null,
            'name' => LocalizedText::resolve($option['name']),
            'carrier_name' => LocalizedText::resolve($option['carrier_name']),
            'provider' => $option['provider'] ?? null,
            'type' => $type instanceof ShippingRateType ? $type->value : $type,
            'rate' => $option['rate'],
            'delivery_time' => LocalizedText::resolve($option['delivery_time']),
        ];
    }
}
