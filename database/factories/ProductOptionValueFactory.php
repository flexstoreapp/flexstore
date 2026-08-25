<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductOptionValue>
 */
final class ProductOptionValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'value' => function (array $attributes) {
                $option = ProductOption::query()->find($attributes['product_option_id']);

                return match ($option?->name) {
                    'Size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
                    'Color' => fake()->colorName(),
                    'Material' => fake()->randomElement(['Cotton', 'Polyester', 'Wool', 'Silk', 'Leather']),
                    'Style' => fake()->randomElement(['Casual', 'Formal', 'Sport', 'Vintage']),
                    default => fake()->word(),
                };
            },
        ];
    }

    public function forProductOption(ProductOption $productOption): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_option_id' => $productOption->id,
        ]);
    }
}
