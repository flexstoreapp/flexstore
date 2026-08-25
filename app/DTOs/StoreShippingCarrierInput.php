<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ShippingCarrierDriver;

final readonly class StoreShippingCarrierInput
{
    /**
     * @param  array<string, string>|string  $name
     */
    public function __construct(
        public array|string $name,
        public ShippingCarrierDriver $driver,
        public bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $driver = $data['driver'];

        return new self(
            name: $data['name'],
            driver: $driver instanceof ShippingCarrierDriver ? $driver : ShippingCarrierDriver::from((string) $driver),
            isActive: (bool) $data['is_active'],
        );
    }
}
