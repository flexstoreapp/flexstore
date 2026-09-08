<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * The order list query selects a reduced column set; this resource stays within it.
 *
 * @mixin Order
 */
final class AdminOrderSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->toIso8601String(),
            'customer_email' => $this->customer_email,
            'customer_name' => $this->customerName(),
            'payment_status' => $this->payment_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'is_canceled' => $this->is_canceled,
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'total' => $this->total,
            'item_count' => (int) $this->resource->getAttributeValue('items_sum_quantity'),
        ];
    }

    private function customerName(): ?string
    {
        $address = $this->resource->billingAddress;

        if ($address === null) {
            return null;
        }

        $name = mb_trim($address->first_name . ' ' . $address->last_name);

        return $name === '' ? null : $name;
    }
}
