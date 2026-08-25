<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
#[UseModel(Coupon::class)]
final class CouponFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(CouponType::cases());
        $value = $type === 'percentage' ? fake()->numberBetween(5, 50) : fake()->numberBetween(500, 5000);

        return [
            'code' => fake()->unique()->lexify('??????'),
            'type' => $type,
            'value' => $value,
            'min_order_value' => fake()->optional(0.7)->numberBetween(1000, 10000),
            'maximum_discount' => $type === 'percentage' ? fake()->optional(0.5)->numberBetween(1000, 5000) : null,
            'usage_limit' => fake()->optional(0.6)->numberBetween(10, 1000),
            'usage_limit_per_customer' => fake()->optional(0.4)->numberBetween(1, 5),
            'used_count' => 0,
            'is_active' => fake()->boolean(80),
            'starts_at' => fake()->optional(0.3)->dateTimeBetween('-1 month', '+1 week'),
            'expires_at' => fake()->optional(0.8)->dateTimeBetween('+1 week', '+6 months'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function fixed(?int $amount = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CouponType::Flat,
            'value' => $amount ?? fake()->numberBetween(500, 5000),
            'maximum_discount' => null,
        ]);
    }

    public function percentage(?int $percent = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'percentage',
            'value' => $percent ?? fake()->numberBetween(5, 50),
            'maximum_discount' => fake()->optional(0.5)->numberBetween(1000, 5000),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    public function withUsageLimit(?int $limit = null, ?int $perCustomer = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_limit' => $limit ?? fake()->numberBetween(10, 100),
            'usage_limit_per_customer' => $perCustomer ?? fake()->numberBetween(1, 3),
        ]);
    }

    public function used(?int $count = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'used_count' => $count ?? fake()->numberBetween(1, $attributes['usage_limit'] ?? 50),
        ]);
    }

    public function valid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
            'starts_at' => null,
            'expires_at' => null,
            'usage_limit' => null,
            'usage_limit_per_customer' => null,
            'min_order_value' => null,
            'maximum_discount' => null,
        ]);
    }
}
