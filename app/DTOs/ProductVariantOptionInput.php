<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ProductVariantOptionInput
{
    public function __construct(
        public string $optionId,
        public string $valueId,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            optionId: (string) $data['option_id'],
            valueId: (string) $data['value_id'],
        );
    }
}
