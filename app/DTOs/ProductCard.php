<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Media;
use App\Models\Product;

final readonly class ProductCard
{
    /**
     * @param  array<string, string>  $title
     * @param  array{name: array<string, string>, url_handle: string}|null  $brand
     * @param  array{string, string}|null  $priceRange
     * @param  array{string, string}|null  $compareAtPriceRange
     */
    public function __construct(
        public int $id,
        public string $urlHandle,
        public array $title,
        public ?array $brand,
        public ?string $price,
        public ?array $priceRange,
        public ?string $compareAtPrice,
        public ?array $compareAtPriceRange,
        public ?Media $featuredMedia,
        public bool $inStock,
        public bool $hasVariants,
        public ?string $createdAt,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        $hasVariants = ($product->variants_count ?? 0) > 0;
        $inStock = $hasVariants && $product->relationLoaded('variants')
            ? $product->variants->contains('in_stock', true)
            : ($product->in_stock ?? false);

        return new self(
            id: $product->id,
            urlHandle: $product->url_handle,
            title: $product->getTranslations('title'),
            brand: $product->brand === null ? null : [
                'name' => $product->brand->getTranslations('name'),
                'url_handle' => $product->brand->url_handle,
            ],
            price: $product->price,
            priceRange: $product->price_range,
            compareAtPrice: $product->compare_at_price,
            compareAtPriceRange: $product->compare_at_price_range,
            featuredMedia: $product->featured_media,
            inStock: $inStock,
            hasVariants: $hasVariants,
            createdAt: $product->created_at->toISOString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url_handle' => $this->urlHandle,
            'title' => $this->title,
            'brand' => $this->brand,
            'price' => $this->price,
            'price_range' => $this->priceRange,
            'compare_at_price' => $this->compareAtPrice,
            'compare_at_price_range' => $this->compareAtPriceRange,
            'featured_media' => $this->featuredMedia,
            'in_stock' => $this->inStock,
            'rating' => null,
            'review_count' => null,
            'has_variants' => $this->hasVariants,
            'created_at' => $this->createdAt,
        ];
    }
}
