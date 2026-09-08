<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\OrderAddressResource;
use App\Http\Resources\Api\V1\OrderItemResource;
use App\Models\Order;
use App\Models\OrderTaxDetail;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Order
 */
final class AdminOrderResource extends JsonResource
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
            'updated_at' => $this->updated_at->toIso8601String(),
            'customer' => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'customer_email' => $this->customer_email,
            'payment_status' => $this->payment_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'is_canceled' => $this->is_canceled,
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason?->value,
            'cancellation_note' => $this->cancellation_note,
            'is_cancellable' => $this->is_cancellable,
            'is_refundable' => $this->is_refundable,
            'is_voidable' => $this->is_voidable,
            'has_outstanding_balance' => $this->has_outstanding_balance,
            'has_credit_owed' => $this->has_credit_owed,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'prices_include_tax' => $this->prices_include_tax,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'shipping_total' => $this->shipping_total,
            'tax_total' => $this->tax_total,
            'total' => $this->total,
            'paid_total' => $this->paid_total,
            'refund_total' => $this->refund_total,
            'balance_due_total' => $this->balance_due_total,
            'credit_due_total' => $this->credit_due_total,
            'coupon_code' => $this->coupon_code,
            'notes' => $this->notes,
            'payment_method' => LocalizedText::resolve($this->payment_gateway_name),
            'shipping_method' => LocalizedText::resolve($this->shipping_rate_name),
            'shipping_carrier' => LocalizedText::resolve($this->shipping_carrier_name),
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
            'activities' => AdminOrderActivityResource::collection($this->activities),
        ];
    }
}
