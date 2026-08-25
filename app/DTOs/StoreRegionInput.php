<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreRegionInput
{
    /**
     * @param  array<string, string>|string  $name
     * @param  array<int, string>  $countries
     * @param  array<int, string>|null  $states
     * @param  array<int, string>|null  $postal_codes
     */
    public function __construct(
        public array|string $name,
        public array $countries,
        public ?array $states,
        public ?array $postal_codes,
        public ?bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            countries: $data['countries'] ?? [],
            states: $data['states'] ?? null,
            postal_codes: $data['postal_codes'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }
}
