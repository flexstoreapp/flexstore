<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductDownload>
 */
final class ProductDownloadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'media_id' => Media::factory()->file(),
            'name' => fake()->words(2, true),
            'sort_order' => 0,
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
    }
}
