<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class OrderItemInput
{
    /**
     * @param  array<string, mixed>|null  $variantOptions
     */
    public function __construct(
        public ?int $id,
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
            id: isset($data['id']) ? (int) $data['id'] : null,
            productId: (int) $data['product_id'],
            productVariantId: isset($data['product_variant_id']) ? (string) $data['product_variant_id'] : null,
            quantity: (int) $data['quantity'],
            variantOptions: isset($data['variant_options']) && is_array($data['variant_options']) ? $data['variant_options'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'quantity' => $this->quantity,
            'variant_options' => $this->variantOptions,
        ];
    }
}
