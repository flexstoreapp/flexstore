<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read array{
 *     refundable_quantities: array<int, int>,
 *     refundable_shipping_amount: string,
 *     max_refundable_amount: string,
 *     supports_gateway_refund: bool,
 *     return: array{id: int, restocking_fee_percent: float, items: array<int, array{order_item_id: int, quantity: int}>}|null
 * } $resource
 */
final class RefundableOrderResource extends JsonResource
{
    public bool $preserveKeys = true;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'refundable_quantities' => $this->resource['refundable_quantities'],
            'refundable_shipping_amount' => $this->resource['refundable_shipping_amount'],
            'max_refundable_amount' => $this->resource['max_refundable_amount'],
            'supports_gateway_refund' => $this->resource['supports_gateway_refund'],
        ];
    }
}
