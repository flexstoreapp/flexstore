<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateRegionInput
{
    /**
     * @param  array<string, string>|string|null  $name
     * @param  array<int, string>|null  $countries
     * @param  array<int, string>|null  $states
     * @param  array<int, string>|null  $postal_codes
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public array|string|null $name,
        public ?array $countries,
        public ?array $states,
        public ?array $postal_codes,
        public ?bool $isActive,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $provided = [];
        foreach (['name', 'countries', 'states', 'postal_codes', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            name: $data['name'] ?? null,
            countries: $data['countries'] ?? null,
            states: $data['states'] ?? null,
            postal_codes: $data['postal_codes'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
