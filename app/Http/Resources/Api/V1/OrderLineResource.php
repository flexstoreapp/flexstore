<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Media;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A line item snapshot from the tracking, payment request and shared payment
 * payloads, where the title is stored as a translation map.
 */
final class OrderLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $line */
        $line = $this->resource;
        $media = $line['featured_media'] ?? $line['thumbnail_url'] ?? null;

        return [
            'product_title' => LocalizedText::resolve($line['product_title'] ?? null),
            'variant_title' => LocalizedText::resolve($line['variant_title'] ?? null),
            'variant_options' => $line['variant_options'] ?? null,
            'url_handle' => $line['url_handle'] ?? null,
            'quantity' => $line['quantity'] ?? null,
            'unit_price' => $line['unit_price'] ?? null,
            'total_price' => $line['total_price'] ?? null,
            'featured_media' => $media instanceof Media ? new MediaResource($media) : $media,
        ];
    }
}
