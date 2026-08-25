<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateCustomerAddressInput
{
    /**
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $addressLine1,
        public ?string $addressLine2,
        public ?string $city,
        public ?string $state,
        public ?string $postal_code,
        public ?string $countryCode,
        public ?string $phone,
        public ?bool $isDefault,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['first_name', 'last_name', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country_code', 'phone', 'is_default'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            firstName: isset($data['first_name']) ? (string) $data['first_name'] : null,
            lastName: isset($data['last_name']) ? (string) $data['last_name'] : null,
            addressLine1: isset($data['address_line_1']) ? (string) $data['address_line_1'] : null,
            addressLine2: isset($data['address_line_2']) ? (string) $data['address_line_2'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            state: isset($data['state']) ? (string) $data['state'] : null,
            postal_code: isset($data['postal_code']) ? (string) $data['postal_code'] : null,
            countryCode: isset($data['country_code']) ? (string) $data['country_code'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            isDefault: isset($data['is_default']) ? (bool) $data['is_default'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
