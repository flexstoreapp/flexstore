<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class AddressLocation
{
    public function __construct(
        public string $countryCode,
        public string $state,
        public string $postalCode,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: $data['country_code'] ?? '',
            state: $data['state'] ?? '',
            postalCode: $data['postal_code'] ?? '',
        );
    }
}
