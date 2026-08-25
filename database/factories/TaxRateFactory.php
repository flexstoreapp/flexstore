<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaxCategory;
use App\Models\Region;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
#[UseModel(TaxRate::class)]
final class TaxRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxName = fake()->randomElement(['VAT', 'GST', 'PST', 'HST']);

        return [
            'name' => $taxName,
            'region_id' => Region::factory(),
            'tax_category' => fake()->boolean(50) ? fake()->randomElement(TaxCategory::cases()) : null,
            'rate' => fake()->randomFloat(2, 0, 20),
            'priority' => fake()->numberBetween(0, 100),
            'min_order_value' => fake()->boolean(100) ? fake()->randomFloat(2, 0.01, 1.00) : null,
            'max_order_value' => fake()->boolean(20) ? fake()->randomFloat(2, 50, 1000) : null,
            'is_compound' => fake()->boolean(20),
            'is_active' => fake()->boolean(90),
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

    public function withoutConditions(): Factory
    {
        return $this->state(fn (array $attributes): array => [
            'min_order_value' => null,
            'max_order_value' => null,
        ]);
    }
}
