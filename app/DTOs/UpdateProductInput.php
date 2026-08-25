<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DimensionUnit;
use App\Enums\ProductType;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;

final readonly class UpdateProductInput
{
    /**
     * @param  array<string, string>|string|null  $title
     * @param  array<string, string>|string|null  $description
     * @param  array<string, string>|string|null  $seoTitle
     * @param  array<string, string>|string|null  $seoDescription
     * @param  list<int>|null  $media
     * @param  list<ProductOptionInput>|null  $options
     * @param  list<ProductVariantInput>|null  $variants
     * @param  list<ProductDownloadInput>|null  $downloads
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public array|string|null $title,
        public ?string $urlHandle,
        public ?ProductType $type,
        public array|string|null $description,
        public ?int $categoryId,
        public ?int $brandId,
        public ?TaxCategory $taxCategory,
        public ?bool $isTaxExempt,
        public ?string $price,
        public ?string $compareAtPrice,
        public ?string $costPerItem,
        public ?string $sku,
        public ?string $barcode,
        public ?bool $trackStock,
        public ?int $stock,
        public ?int $lowStockThreshold,
        public ?bool $inStock,
        public ?bool $isActive,
        public ?string $weight,
        public ?WeightUnit $weightUnit,
        public ?string $length,
        public ?string $width,
        public ?string $height,
        public ?DimensionUnit $dimensionUnit,
        public ?array $media,
        public array|string|null $seoTitle,
        public array|string|null $seoDescription,
        public ?array $options,
        public ?array $variants,
        public array $provided,
        public ?array $downloads,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['title', 'url_handle', 'type', 'description', 'category_id', 'brand_id', 'tax_category', 'is_tax_exempt', 'price', 'compare_at_price', 'cost_per_item', 'sku', 'barcode', 'track_stock', 'stock', 'low_stock_threshold', 'in_stock', 'is_active', 'weight', 'weight_unit', 'length', 'width', 'height', 'dimension_unit', 'media', 'seo_title', 'seo_description', 'options', 'variants', 'downloads'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $taxCategory = null;
        if (array_key_exists('tax_category', $data) && $data['tax_category'] !== null) {
            $taxCategory = $data['tax_category'] instanceof TaxCategory
                ? $data['tax_category']
                : TaxCategory::from((string) $data['tax_category']);
        }

        $weightUnit = null;
        if (array_key_exists('weight_unit', $data) && $data['weight_unit'] !== null) {
            $weightUnit = $data['weight_unit'] instanceof WeightUnit
                ? $data['weight_unit']
                : WeightUnit::from((string) $data['weight_unit']);
        }

        $dimensionUnit = null;
        if (array_key_exists('dimension_unit', $data) && $data['dimension_unit'] !== null) {
            $dimensionUnit = $data['dimension_unit'] instanceof DimensionUnit
                ? $data['dimension_unit']
                : DimensionUnit::from((string) $data['dimension_unit']);
        }

        return new self(
            title: $data['title'] ?? null,
            urlHandle: array_key_exists('url_handle', $data) && $data['url_handle'] !== null ? (string) $data['url_handle'] : null,
            type: array_key_exists('type', $data) && $data['type'] !== null
                ? ($data['type'] instanceof ProductType ? $data['type'] : ProductType::from((string) $data['type']))
                : null,
            description: $data['description'] ?? null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            taxCategory: $taxCategory,
            isTaxExempt: isset($data['is_tax_exempt']) ? (bool) $data['is_tax_exempt'] : null,
            price: isset($data['price']) ? (string) $data['price'] : null,
            compareAtPrice: isset($data['compare_at_price']) ? (string) $data['compare_at_price'] : null,
            costPerItem: isset($data['cost_per_item']) ? (string) $data['cost_per_item'] : null,
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            barcode: isset($data['barcode']) ? (string) $data['barcode'] : null,
            trackStock: isset($data['track_stock']) ? (bool) $data['track_stock'] : null,
            stock: isset($data['stock']) ? (int) $data['stock'] : null,
            lowStockThreshold: isset($data['low_stock_threshold']) ? (int) $data['low_stock_threshold'] : null,
            inStock: isset($data['in_stock']) ? (bool) $data['in_stock'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            weight: isset($data['weight']) ? (string) $data['weight'] : null,
            weightUnit: $weightUnit,
            length: isset($data['length']) ? (string) $data['length'] : null,
            width: isset($data['width']) ? (string) $data['width'] : null,
            height: isset($data['height']) ? (string) $data['height'] : null,
            dimensionUnit: $dimensionUnit,
            media: isset($data['media']) && is_array($data['media']) ? array_values(array_map(intval(...), $data['media'])) : null,
            seoTitle: $data['seo_title'] ?? null,
            seoDescription: $data['seo_description'] ?? null,
            options: isset($data['options']) && is_array($data['options'])
                ? array_values(array_map(ProductOptionInput::fromArray(...), $data['options']))
                : null,
            variants: isset($data['variants']) && is_array($data['variants'])
                ? array_values(array_map(ProductVariantInput::fromArray(...), $data['variants']))
                : null,
            provided: $provided,
            downloads: isset($data['downloads']) && is_array($data['downloads'])
                ? array_values(array_map(ProductDownloadInput::fromArray(...), $data['downloads']))
                : null,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
