<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Media;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps the array shape produced by StorefrontProductListQuery.
 */
final class ProductCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $product */
        $product = $this->resource;

        /** @var array{name: mixed, url_handle: string}|null $brand */
        $brand = $product['brand'] ?? null;
        $media = $product['featured_media'] ?? null;

        return [
            'id' => $product['id'],
            'url_handle' => $product['url_handle'],
            'title' => LocalizedText::resolve($product['title']),
            'brand' => $brand === null ? null : [
                'name' => LocalizedText::resolve($brand['name']),
                'url_handle' => $brand['url_handle'],
            ],
            'price' => $product['price'],
            'price_range' => $product['price_range'],
            'compare_at_price' => $product['compare_at_price'],
            'compare_at_price_range' => $product['compare_at_price_range'],
            'featured_media' => $media instanceof Media ? new MediaResource($media) : null,
            'in_stock' => $product['in_stock'],
            'has_variants' => $product['has_variants'],
            ...array_key_exists('rating', $product) ? ['rating' => $product['rating']] : [],
            ...array_key_exists('review_count', $product) ? ['review_count' => $product['review_count']] : [],
            'flash_sale' => $product['flash_sale'] ?? null,
            'created_at' => $product['created_at'],
        ];
    }
}
