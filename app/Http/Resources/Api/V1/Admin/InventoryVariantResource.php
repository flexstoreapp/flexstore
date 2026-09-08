<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use App\Models\ProductVariant;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin ProductVariant
 */
final class InventoryVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $media = $this->relationLoaded('media') ? $this->media : null;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'title' => LocalizedText::resolve($this->title),
            'sku' => $this->sku,
            'track_stock' => $this->track_stock,
            'stock' => $this->stock,
            'low_stock_threshold' => $this->low_stock_threshold,
            'in_stock' => $this->in_stock,
            'is_low_stock' => $this->is_low_stock,
            'media' => $media instanceof Media ? new MediaResource($media) : null,
        ];
    }
}
