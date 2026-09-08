<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read Media $resource
 */
final class DigitalFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->original_filename,
            'original_filename' => $this->resource->original_filename,
            'mime_type' => $this->resource->mime_type,
            'size' => $this->resource->size,
        ];
    }
}
