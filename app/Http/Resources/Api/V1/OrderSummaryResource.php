<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Order
 */
final class OrderSummaryResource extends JsonResource
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
            'currency_code' => $this->currency_code,
            'total' => $this->total,
            'item_count' => $this->items_count,
            'items' => OrderItemSummaryResource::collection($this->items),
        ];
    }
}
