<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Media;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps a single entry from ProductSearchSuggestionsQuery.
 */
final class SearchSuggestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $suggestion */
        $suggestion = $this->resource;
        $media = $suggestion['featured_media'];

        return [
            'id' => $suggestion['id'],
            'url_handle' => $suggestion['url_handle'],
            'title' => LocalizedText::resolve($suggestion['title']),
            'category' => LocalizedText::resolve($suggestion['category']),
            'price' => $suggestion['price'],
            'price_range' => $suggestion['price_range'],
            'compare_at_price' => $suggestion['compare_at_price'],
            'compare_at_price_range' => $suggestion['compare_at_price_range'],
            'featured_media' => $media instanceof Media ? new MediaResource($media) : null,
            'in_stock' => $suggestion['in_stock'],
            'flash_sale' => $suggestion['flash_sale'] ?? null,
        ];
    }
}
