<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DimensionUnit;
use App\Enums\WeightUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductVariant>
 */
final class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'title' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 10, 1000),
            'compare_at_price' => fake()->optional(0.3)->randomFloat(2, 10, 1000),
            'cost_per_item' => fake()->optional(0.5)->randomFloat(2, 5, 500),
            'sku' => fake()->optional(0.8)->bothify('SKU-####??'),
            'barcode' => fake()->optional(0.6)->ean13(),
            'track_stock' => $trackStock = fake()->boolean(),
            'stock' => $trackStock ? fake()->numberBetween(0, 100) : null,
            'in_stock' => fake()->boolean(80),
            'weight' => fake()->randomFloat(2, 0.1, 10),
            'weight_unit' => fake()->randomElement(WeightUnit::cases()),
            'length' => fake()->randomFloat(2, 5, 45),
            'width' => fake()->randomFloat(2, 5, 35),
            'height' => fake()->randomFloat(2, 2, 30),
            'dimension_unit' => DimensionUnit::Cm,
            'is_default' => false,
        ];
    }
}
