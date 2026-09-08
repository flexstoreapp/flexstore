<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\ShippingCarrier;
use App\Utilities\LocalizedText;
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
        $carrier = $this->carrier;

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'carrier' => $carrier instanceof ShippingCarrier ? [
                'id' => $carrier->id,
                'name' => LocalizedText::resolve($carrier->getTranslations('name')),
                'driver' => $carrier->driver->value,
            ] : null,
            'items' => $this->items->map(fn (OrderShipmentItem $item): array => [
                'order_item_id' => $item->order_item_id,
                'title' => LocalizedText::resolve($item->orderItem->getTranslations('product_title')),
                'variant_title' => $item->orderItem->variant_title,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }
}
