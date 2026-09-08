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
final class OrderPaymentSummaryResource extends JsonResource
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
            'currency_code' => $this->currency_code,
            'total' => $this->total,
            'paid_total' => $this->paid_total,
            'refund_total' => $this->refund_total,
            'net_paid_total' => $this->net_paid_total,
            'balance_due_total' => $this->balance_due_total,
            'credit_due_total' => $this->credit_due_total,
        ];
    }
}
