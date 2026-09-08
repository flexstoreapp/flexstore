<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Media
 */
final class AdminMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type->value,
            'url' => $this->resource->url,
            'thumbnail_url' => $this->resource->thumbnail_url,
            'small_thumbnail_url' => $this->resource->small_thumbnail_url,
            'alt' => $this->resource->alt,
            'width' => $this->resource->width,
            'height' => $this->resource->height,
            'mime_type' => $this->resource->mime_type,
            'size' => $this->resource->size,
            'original_filename' => $this->resource->original_filename,
        ];
    }
}
