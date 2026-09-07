<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Media;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps the array shape produced by StorefrontBrandListQuery.
 */
final class BrandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $brand */
        $brand = $this->resource;
        $image = $brand['image'] ?? null;

        return [
            'id' => $brand['id'],
            'name' => LocalizedText::resolve($brand['name']),
            'url_handle' => $brand['url_handle'],
            'image' => $image instanceof Media ? new MediaResource($image) : null,
        ];
    }
}
