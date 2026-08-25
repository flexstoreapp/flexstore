<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ShippingCarrierDriver;
use App\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingCarrier>
 */
#[UseModel(ShippingCarrier::class)]
final class ShippingCarrierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Shipping',
            'driver' => ShippingCarrierDriver::Manual,
            'is_active' => fake()->boolean(80),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function manual(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => ShippingCarrierDriver::Manual,
            'name' => 'Custom Shipping',
        ]);
    }
}
