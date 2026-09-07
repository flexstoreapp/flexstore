<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Media;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Wraps the array shape produced by ProductDetailQuery.
 */
final class ProductResource extends JsonResource
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
        $brand = $product['brand'];
        /** @var array{name: mixed, url_handle: string, ancestors: list<array{name: mixed, url_handle: string}>}|null $category */
        $category = $product['category'];
        /** @var list<Media> $media */
        $media = $product['media'];
        $featured = $product['featured_media'];

        return [
            'id' => $product['id'],
            'url_handle' => $product['url_handle'],
            'title' => LocalizedText::resolve($product['title']),
            'description' => LocalizedText::resolve($product['description']),
            'type' => $product['type'],
            'sku' => $product['sku'],
            'barcode' => $product['barcode'],
            'brand' => $brand === null ? null : [
                'name' => LocalizedText::resolve($brand['name']),
                'url_handle' => $brand['url_handle'],
            ],
            'category' => $category === null ? null : [
                'name' => LocalizedText::resolve($category['name']),
                'url_handle' => $category['url_handle'],
                'ancestors' => array_map(fn (array $ancestor): array => [
                    'name' => LocalizedText::resolve($ancestor['name']),
                    'url_handle' => $ancestor['url_handle'],
                ], $category['ancestors']),
            ],
            'price' => $product['price'],
            'price_range' => $product['price_range'],
            'compare_at_price' => $product['compare_at_price'],
            'compare_at_price_range' => $product['compare_at_price_range'],
            'prices_include_tax' => $product['prices_include_tax'],
            'flash_sale' => $product['flash_sale'] ?? null,
            'flash_price_range' => $product['flash_price_range'] ?? null,
            'in_stock' => $product['in_stock'],
            'max_quantity' => $product['max_quantity'],
            'rating' => $product['rating'],
            'review_count' => $product['review_count'],
            'rating_distribution' => $product['rating_distribution'],
            'has_variants' => $product['has_variants'],
            'media' => MediaResource::collection($media),
            'featured_media' => $featured instanceof Media ? new MediaResource($featured) : null,
            'options' => $this->options($product['options']),
            'variants' => $this->variants($product['variants']),
            'seo_title' => LocalizedText::resolve($product['seo_title']),
            'seo_description' => LocalizedText::resolve($product['seo_description']),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    private function options(array $options): array
    {
        return array_map(fn (array $option): array => [
            'id' => $option['id'],
            'name' => LocalizedText::resolve($option['name']),
            'values' => array_map(fn (array $value): array => [
                'id' => $value['id'],
                'value' => LocalizedText::resolve($value['value']),
            ], $option['values']),
        ], $options);
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     * @return list<array<string, mixed>>
     */
    private function variants(array $variants): array
    {
        return array_map(fn (array $variant): array => [
            'id' => $variant['id'],
            'title' => LocalizedText::resolve($variant['title']),
            'sku' => $variant['sku'],
            'barcode' => $variant['barcode'],
            'price' => $variant['price'],
            'compare_at_price' => $variant['compare_at_price'],
            'flash_sale_price' => $variant['flash_sale_price'] ?? null,
            'in_stock' => $variant['in_stock'],
            'is_default' => $variant['is_default'],
            'max_quantity' => $variant['max_quantity'],
            'media' => $variant['media'] instanceof Media ? new MediaResource($variant['media']) : null,
            'option_values' => $variant['option_values'],
        ], $variants);
    }
}
