<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps the array shape produced by StorefrontShopFacetsQuery.
 */
final class ProductFacetsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $facets */
        $facets = $this->resource;

        return [
            'categories' => $this->named($facets['categories']),
            'brands' => $this->named($facets['brands']),
            'price_buckets' => $facets['price_buckets'],
            'rating_buckets' => $facets['rating_buckets'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $entries
     * @return list<array<string, mixed>>|null
     */
    private function named(?array $entries): ?array
    {
        if ($entries === null) {
            return null;
        }

        return array_map(fn (array $entry): array => [
            'id' => $entry['id'],
            'name' => LocalizedText::resolve($entry['name']),
            'url_handle' => $entry['url_handle'],
            'count' => $entry['count'],
        ], $entries);
    }
}
