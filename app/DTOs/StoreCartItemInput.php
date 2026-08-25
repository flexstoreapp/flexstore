<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreCartItemInput
{
    /**
     * @param  array<string, mixed>|null  $variantOptions
     */
    public function __construct(
        public int $productId,
        public ?string $productVariantId,
        public int $quantity,
        public ?array $variantOptions,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            productVariantId: isset($data['product_variant_id']) ? (string) $data['product_variant_id'] : null,
            quantity: (int) $data['quantity'],
            variantOptions: isset($data['variant_options']) && is_array($data['variant_options']) ? $data['variant_options'] : null,
        );
    }
}
