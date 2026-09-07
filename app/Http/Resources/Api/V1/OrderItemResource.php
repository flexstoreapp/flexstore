<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin OrderItem
 */
final class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'url_handle' => $this->product?->is_active === true ? $this->product->url_handle : null,
            'title' => $this->product_title,
            'variant_title' => $this->variant_title,
            'variant_options' => $this->variant_options,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'requires_shipping' => $this->requires_shipping,
            'media' => $this->media === null ? null : new MediaResource($this->media),
        ];
    }
}
