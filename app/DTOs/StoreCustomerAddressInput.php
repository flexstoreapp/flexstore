<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreCustomerAddressInput
{
    public function __construct(
        public Address $address,
        public bool $isDefault,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            address: Address::fromArray($data),
            isDefault: (bool) ($data['is_default'] ?? false),
        );
    }
}
