<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRate>
 */
#[UseModel(ShippingRate::class)]
final class ShippingRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'shipping_carrier_id' => ShippingCarrier::factory(),
            'name' => fake()->countryCode(),
            'type' => fake()->randomElement([ShippingRateType::Flat, ShippingRateType::Free]),
            'rate' => fake()->randomFloat(2, 5, 50),
            'delivery_time' => fake()->randomNumber(1) . ' business days',
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

    public function flatRate(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'flat',
        ]);
    }

    public function withConditions(): self
    {
        return $this->state(fn (array $attributes): array => [
            'min_order_value' => fake()->randomFloat(2, 0, 50),
            'max_order_value' => fake()->randomFloat(2, 50, 200),
            'min_weight' => fake()->randomFloat(2, 0, 5),
            'min_weight_unit' => fake()->randomElement(WeightUnit::cases()),
            'max_weight' => fake()->randomFloat(2, 5, 20),
            'max_weight_unit' => fake()->randomElement(WeightUnit::cases()),
        ]);
    }

    public function withRestrictions(): self
    {
        return $this->state(fn (array $attributes): array => [
            'excluded_products' => [1, 2, 3],
            'excluded_categories' => [1, 2],
            'excluded_brands' => [1, 2],
        ]);
    }

    public function free(): self
    {
        return $this->state(fn (array $attributes): array => [
            'rate' => 0,
            'name' => 'Free Shipping',
        ]);
    }

    public function live(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShippingRateType::Live,
            'rate' => null,
            'name' => 'Live Rates',
            'delivery_time' => null,
        ]);
    }
}
