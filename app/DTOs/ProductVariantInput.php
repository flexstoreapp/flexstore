<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DimensionUnit;
use App\Enums\WeightUnit;

final readonly class ProductVariantInput
{
    /**
     * @param  list<ProductVariantOptionInput>  $options
     */
    public function __construct(
        public ?string $id,
        public string $title,
        public ?string $price,
        public ?string $compareAtPrice,
        public ?string $costPerItem,
        public ?string $sku,
        public ?string $barcode,
        public bool $trackStock,
        public ?int $stock,
        public ?int $lowStockThreshold,
        public bool $inStock,
        public ?string $weight,
        public ?WeightUnit $weightUnit,
        public ?string $length,
        public ?string $width,
        public ?string $height,
        public ?DimensionUnit $dimensionUnit,
        public ?int $mediaId,
        public bool $isDefault,
        public array $options,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
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

        $options = [];
        foreach ($data['options'] ?? [] as $option) {
            $options[] = ProductVariantOptionInput::fromArray($option);
        }

        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            title: (string) ($data['title'] ?? ''),
            price: isset($data['price']) ? (string) $data['price'] : null,
            compareAtPrice: isset($data['compare_at_price']) ? (string) $data['compare_at_price'] : null,
            costPerItem: isset($data['cost_per_item']) ? (string) $data['cost_per_item'] : null,
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            barcode: isset($data['barcode']) ? (string) $data['barcode'] : null,
            trackStock: (bool) ($data['track_stock'] ?? false),
            stock: isset($data['stock']) ? (int) $data['stock'] : null,
            lowStockThreshold: isset($data['low_stock_threshold']) ? (int) $data['low_stock_threshold'] : null,
            inStock: (bool) ($data['in_stock'] ?? true),
            weight: isset($data['weight']) ? (string) $data['weight'] : null,
            weightUnit: $weightUnit,
            length: isset($data['length']) ? (string) $data['length'] : null,
            width: isset($data['width']) ? (string) $data['width'] : null,
            height: isset($data['height']) ? (string) $data['height'] : null,
            dimensionUnit: $dimensionUnit,
            mediaId: isset($data['media_id']) && $data['media_id'] !== '' ? (int) $data['media_id'] : null,
            isDefault: (bool) ($data['is_default'] ?? false),
            options: $options,
        );
    }
}
