<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin OrderShipment
 */
final class OrderShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'items' => $this->items->map(fn (OrderShipmentItem $item): array => [
                'order_item_id' => $item->order_item_id,
                'title' => $item->orderItem->product_title,
                'variant_title' => $item->orderItem->variant_title,
                'quantity' => $item->quantity,
            ])->all(),
        ];
    }
}
