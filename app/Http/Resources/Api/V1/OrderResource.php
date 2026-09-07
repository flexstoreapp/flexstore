<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use App\Models\OrderTaxDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
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
            'payment_status' => $this->payment_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason?->value,
            'currency_code' => $this->currency_code,
            'prices_include_tax' => $this->prices_include_tax,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'shipping_total' => $this->shipping_total,
            'tax_total' => $this->tax_total,
            'total' => $this->total,
            'paid_total' => $this->paid_total,
            'refund_total' => $this->refund_total,
            'balance_due_total' => $this->balance_due_total,
            'coupon_code' => $this->coupon_code,
            'notes' => $this->notes,
            'payment_method' => $this->payment_gateway_name,
            'shipping_method' => $this->shipping_rate_name,
            'shipping_carrier' => $this->shipping_carrier_name,
            'items' => OrderItemResource::collection($this->items),
            'shipping_address' => $this->shippingAddress === null ? null : new OrderAddressResource($this->shippingAddress),
            'billing_address' => $this->billingAddress === null ? null : new OrderAddressResource($this->billingAddress),
            'tax_details' => $this->taxDetails->map(fn (OrderTaxDetail $detail): array => [
                'tax_name' => $detail->tax_name,
                'tax_rate' => $detail->tax_rate,
                'taxable_amount' => $detail->taxable_amount,
                'tax_amount' => $detail->tax_amount,
            ])->all(),
            'shipments' => OrderShipmentResource::collection($this->shipments),
            'refunds' => OrderRefundResource::collection($this->refunds),
            'downloads' => OrderDownloadResource::collection($this->itemDownloads),
        ];
    }
}
