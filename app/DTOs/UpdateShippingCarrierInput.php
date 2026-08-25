<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ShippingCarrierDriver;

final readonly class UpdateShippingCarrierInput
{
    /**
     * @param  array<string, string>|string|null  $name
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public array|string|null $name,
        public ?ShippingCarrierDriver $driver,
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
        foreach (['name', 'driver', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $driver = null;
        if (array_key_exists('driver', $data) && $data['driver'] !== null) {
            $driver = $data['driver'] instanceof ShippingCarrierDriver
                ? $data['driver']
                : ShippingCarrierDriver::from((string) $data['driver']);
        }

        return new self(
            name: $data['name'] ?? null,
            driver: $driver,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
