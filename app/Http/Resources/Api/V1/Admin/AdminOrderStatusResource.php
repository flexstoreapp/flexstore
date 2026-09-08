<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Order
 */
final class AdminOrderStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_status' => $this->payment_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'is_canceled' => $this->is_canceled,
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason?->value,
            'cancellation_note' => $this->cancellation_note,
            'is_cancellable' => $this->is_cancellable,
            'is_refundable' => $this->is_refundable,
            'currency_code' => $this->currency_code,
            'total' => $this->total,
            'paid_total' => $this->paid_total,
            'refund_total' => $this->refund_total,
            'balance_due_total' => $this->balance_due_total,
        ];
    }
}
