<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ProductOptionValueInput
{
    public function __construct(
        public string $id,
        public string $value,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            value: (string) $data['value'],
        );
    }
}
