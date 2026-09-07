<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * The order list query selects a reduced column set; this resource stays within it.
 *
 * @mixin OrderItem
 */
final class OrderItemSummaryResource extends JsonResource
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
            'url_handle' => $this->product?->is_active === true ? $this->product->url_handle : null,
            'title' => $this->product_title,
            'variant_title' => $this->variant_title,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'media' => $this->media === null ? null : new MediaResource($this->media),
        ];
    }
}
