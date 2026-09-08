<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Brand;
use App\Models\Media;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Brand
 */
final class BrandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => LocalizedText::resolve($this->resource->name),
            'url_handle' => $this->resource->url_handle,
            'description' => LocalizedText::resolve($this->resource->description),
            'seo_title' => LocalizedText::resolve($this->resource->seo_title),
            'seo_description' => LocalizedText::resolve($this->resource->seo_description),
            'image' => $this->resource->image instanceof Media ? new MediaResource($this->resource->image) : null,
            'is_active' => $this->resource->is_active,
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
