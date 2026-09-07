<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin CartItem
 */
final class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $media = $this->productVariant->media ?? $this->product?->featured_media;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'url_handle' => $this->product?->url_handle,
            'title' => $this->product?->title,
            'variant_title' => $this->variant_title,
            'variant_options' => $this->variant_options,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'compare_at_price' => $this->compare_at_price,
            'total_price' => $this->total_price,
            'featured_media' => $media === null ? null : new MediaResource($media),
        ];
    }
}
