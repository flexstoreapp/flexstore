<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ProductOptionInput
{
    /**
     * @param  list<ProductOptionValueInput>  $values
     */
    public function __construct(
        public string $id,
        public ?string $name,
        public array $values,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $values = [];
        foreach ($data['values'] ?? [] as $value) {
            $values[] = ProductOptionValueInput::fromArray($value);
        }

        return new self(
            id: (string) $data['id'],
            name: isset($data['name']) ? (string) $data['name'] : null,
            values: $values,
        );
    }
}
