<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DimensionUnit;
use App\Enums\ProductType;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;

final readonly class StoreProductInput
{
    /**
     * @param  array<string, string>|string  $title
     * @param  array<string, string>|string|null  $description
     * @param  array<string, string>|string|null  $seoTitle
     * @param  array<string, string>|string|null  $seoDescription
     * @param  list<int>  $media
     * @param  list<ProductOptionInput>|null  $options
     * @param  list<ProductVariantInput>|null  $variants
     * @param  list<ProductDownloadInput>|null  $downloads
     */
    public function __construct(
        public array|string $title,
        public ProductType $type,
        public ?string $urlHandle,
        public array|string|null $description,
        public ?int $categoryId,
        public ?int $brandId,
        public ?TaxCategory $taxCategory,
        public bool $isTaxExempt,
        public ?string $price,
        public ?string $compareAtPrice,
        public ?string $costPerItem,
        public ?string $sku,
        public ?string $barcode,
        public bool $trackStock,
        public ?int $stock,
        public ?int $lowStockThreshold,
        public bool $inStock,
        public bool $isActive,
        public ?string $weight,
        public ?WeightUnit $weightUnit,
        public ?string $length,
        public ?string $width,
        public ?string $height,
        public ?DimensionUnit $dimensionUnit,
        public array $media,
        public array|string|null $seoTitle,
        public array|string|null $seoDescription,
        public ?array $options,
        public ?array $variants,
        public ?array $downloads,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
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
            title: $data['title'],
            type: $data['type'] instanceof ProductType
                ? $data['type']
                : ProductType::tryFrom((string) ($data['type'] ?? '')) ?? ProductType::Physical,
            urlHandle: isset($data['url_handle']) ? (string) $data['url_handle'] : null,
            description: $data['description'] ?? null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            taxCategory: $taxCategory,
            isTaxExempt: (bool) ($data['is_tax_exempt'] ?? false),
            price: isset($data['price']) ? (string) $data['price'] : null,
            compareAtPrice: isset($data['compare_at_price']) ? (string) $data['compare_at_price'] : null,
            costPerItem: isset($data['cost_per_item']) ? (string) $data['cost_per_item'] : null,
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            barcode: isset($data['barcode']) ? (string) $data['barcode'] : null,
            trackStock: (bool) ($data['track_stock'] ?? false),
            stock: isset($data['stock']) ? (int) $data['stock'] : null,
            lowStockThreshold: isset($data['low_stock_threshold']) ? (int) $data['low_stock_threshold'] : null,
            inStock: (bool) ($data['in_stock'] ?? false),
            isActive: (bool) ($data['is_active'] ?? false),
            weight: isset($data['weight']) ? (string) $data['weight'] : null,
            weightUnit: $weightUnit,
            length: isset($data['length']) ? (string) $data['length'] : null,
            width: isset($data['width']) ? (string) $data['width'] : null,
            height: isset($data['height']) ? (string) $data['height'] : null,
            dimensionUnit: $dimensionUnit,
            media: isset($data['media']) && is_array($data['media']) ? array_values(array_map(intval(...), $data['media'])) : [],
            seoTitle: $data['seo_title'] ?? null,
            seoDescription: $data['seo_description'] ?? null,
            options: isset($data['options']) && is_array($data['options'])
                ? array_values(array_map(ProductOptionInput::fromArray(...), $data['options']))
                : null,
            variants: isset($data['variants']) && is_array($data['variants'])
                ? array_values(array_map(ProductVariantInput::fromArray(...), $data['variants']))
                : null,
            downloads: isset($data['downloads']) && is_array($data['downloads'])
                ? array_values(array_map(ProductDownloadInput::fromArray(...), $data['downloads']))
                : null,
        );
    }
}
