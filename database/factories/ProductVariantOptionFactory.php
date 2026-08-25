<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductVariantOption>
 */
final class ProductVariantOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'product_option_id' => ProductOption::factory(),
            'product_option_value_id' => fn (array $attributes) => ProductOptionValue::factory()->create([
                'product_option_id' => $attributes['product_option_id'],
            ])->id,
        ];
    }
}
