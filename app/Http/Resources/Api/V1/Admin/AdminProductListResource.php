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
 * @property-read Product $resource
 */
final class AdminProductListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $category = $this->resource->category;
        $media = $this->resource->featured_media;

        return [
            'id' => $this->resource->id,
            'title' => LocalizedText::resolve($this->resource->getTranslations('title')),
            'price' => $this->resource->price,
            'price_range' => $this->resource->price_range,
            'total_stock' => $this->resource->total_stock,
            'track_stock' => $this->resource->track_stock,
            'is_active' => $this->resource->is_active,
            'category' => $category === null ? null : [
                'id' => $category->id,
                'name' => LocalizedText::resolve($category->getTranslations('name')),
            ],
            'featured_media' => $media instanceof Media ? new MediaResource($media) : null,
        ];
    }
}
