<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DimensionUnit;
use App\Enums\WeightUnit;
use App\Models\Product;
use App\Models\ProductVariant;

final readonly class HydratedOrderItem
{
    /**
     * @param  ?array<string, mixed>  $variantOptions
     * @param  array<string, mixed>  $productTitle
     */
    public function __construct(
        public Product $product,
        public ?ProductVariant $variant,
        public string $unitPrice,
        public string $totalPrice,
        public int $quantity,
        public ?array $variantOptions,
        public array $productTitle,
        public string $productSku,
        public ?string $variantTitle,
        public bool $requiresShipping,
        public ?string $weight,
        public ?WeightUnit $weightUnit,
        public ?string $length = null,
        public ?string $width = null,
        public ?string $height = null,
        public ?DimensionUnit $dimensionUnit = null,
    ) {
    }

    public function withPrices(string $unitPrice, string $totalPrice): self
    {
        return new self(
            product: $this->product,
            variant: $this->variant,
            unitPrice: $unitPrice,
            totalPrice: $totalPrice,
            quantity: $this->quantity,
            variantOptions: $this->variantOptions,
            productTitle: $this->productTitle,
            productSku: $this->productSku,
            variantTitle: $this->variantTitle,
            requiresShipping: $this->requiresShipping,
            weight: $this->weight,
            weightUnit: $this->weightUnit,
            length: $this->length,
            width: $this->width,
            height: $this->height,
            dimensionUnit: $this->dimensionUnit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_title' => $this->productTitle,
            'product_sku' => $this->productSku,
            'variant_title' => $this->variantTitle,
            'variant_options' => $this->variantOptions,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice,
            'requires_shipping' => $this->requiresShipping,
            'weight' => $this->weight,
            'weight_unit' => $this->weightUnit,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'dimension_unit' => $this->dimensionUnit,
        ];
    }
}
