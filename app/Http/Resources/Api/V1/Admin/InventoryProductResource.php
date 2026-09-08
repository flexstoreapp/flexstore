<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use App\Models\Product;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Product
 */
final class InventoryProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $media = $this->relationLoaded('mediaGallery') ? $this->featured_media : null;

        return [
            'id' => $this->id,
            'title' => LocalizedText::resolve($this->title),
            'sku' => $this->sku,
            'is_active' => $this->is_active,
            'track_stock' => $this->track_stock,
            'stock' => $this->stock,
            'total_stock' => $this->total_stock,
            'low_stock_threshold' => $this->low_stock_threshold,
            'in_stock' => $this->in_stock,
            'is_low_stock' => $this->is_low_stock,
            'featured_media' => $media instanceof Media ? new MediaResource($media) : null,
            'variants' => InventoryVariantResource::collection($this->variants),
        ];
    }
}
