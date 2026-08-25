<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class Address
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public ?string $state,
        public ?string $postalCode,
        public string $countryCode,
        public ?string $phone,
        public ?string $email = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
            addressLine1: (string) ($data['address_line_1'] ?? ''),
            addressLine2: isset($data['address_line_2']) ? (string) $data['address_line_2'] : null,
            city: (string) ($data['city'] ?? ''),
            state: self::nullableString($data['state'] ?? null),
            postalCode: self::nullableString($data['postal_code'] ?? null),
            countryCode: (string) ($data['country_code'] ?? ''),
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            email: self::nullableString($data['email'] ?? null),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
