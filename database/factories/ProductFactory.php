<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Actions\SyncMediaAction;
use App\Enums\DimensionUnit;
use App\Enums\ProductType;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
final class ProductFactory extends Factory
{
    public function definition(): array
    {
        /** @var string $title */
        $title = fake()->unique()->words(3, true);

        return [
            'type' => ProductType::Physical,
            'title' => $title,
            'url_handle' => Str::slug($title),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 9.99, 999.99),
            'compare_at_price' => fake()->optional(0.3)->randomFloat(2, 10.99, 1299.99),
            'cost_per_item' => fake()->optional(0.7)->randomFloat(2, 5.99, 499.99),
            'sku' => fake()->unique()->regexify('[A-Z]{3}[0-9]{5}'),
            'barcode' => fake()->optional(0.6)->ean13(),
            'track_stock' => fake()->boolean(90),
            'stock' => fake()->numberBetween(0, 100),
            'in_stock' => fake()->boolean(70),
            'weight' => fake()->randomFloat(2, 0.1, 20),
            'weight_unit' => fake()->randomElement(WeightUnit::cases()),
            'length' => fake()->randomFloat(2, 5, 45),
            'width' => fake()->randomFloat(2, 5, 35),
            'height' => fake()->randomFloat(2, 2, 30),
            'dimension_unit' => DimensionUnit::Cm,
            'is_tax_exempt' => fake()->boolean(50),
            'tax_category' => fn (array $attributes): ?TaxCategory => $attributes['is_tax_exempt']
                ? null
                : fake()->randomElement(TaxCategory::cases()),
            'is_active' => fake()->boolean(80),
            'seo_title' => $title,
            'seo_description' => fake()->optional(0.6)->text(160),
        ];
    }

    public function withMedia(int $count = 2): static
    {
        return $this->afterCreating(function (Product $product) use ($count): void {
            $ids = Media::factory()->count($count)->create()->pluck('id')->all();

            new SyncMediaAction()->handle($product, $ids);
        });
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

    public function inStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_stock' => true,
            'stock' => 100,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_stock' => false,
            'stock' => 0,
        ]);
    }

    public function available(): static
    {
        return $this->active()->inStock();
    }

    public function withVariants(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => null,
            'compare_at_price' => null,
            'cost_per_item' => null,
            'sku' => null,
            'barcode' => null,
            'track_stock' => null,
            'stock' => null,
            'in_stock' => null,
            'weight' => null,
            'weight_unit' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'dimension_unit' => null,
        ])->afterCreating(function ($product): void {
            $sizeOption = ProductOption::factory()->create([
                'product_id' => $product->id,
                'name' => 'Size',
            ]);

            $sizeValues = [];
            foreach (['S', 'M', 'L'] as $size) {
                $sizeValues[] = ProductOptionValue::factory()->create([
                    'product_option_id' => $sizeOption->id,
                    'value' => $size,
                ]);
            }

            $colorOption = ProductOption::factory()->create([
                'product_id' => $product->id,
                'name' => 'Color',
            ]);

            $colorValues = [];
            foreach (['Red', 'Blue', 'Green'] as $color) {
                $colorValues[] = ProductOptionValue::factory()->create([
                    'product_option_id' => $colorOption->id,
                    'value' => $color,
                ]);
            }

            $isFirstVariant = true;

            foreach ($sizeValues as $sizeValue) {
                foreach ($colorValues as $colorValue) {
                    $variant = ProductVariant::factory()->create([
                        'product_id' => $product->id,
                        'title' => "{$sizeValue->value} / {$colorValue->value}",
                        'is_default' => $isFirstVariant,
                    ]);

                    ProductVariantOption::query()->create([
                        'product_variant_id' => $variant->id,
                        'product_option_id' => $sizeOption->id,
                        'product_option_value_id' => $sizeValue->id,
                    ]);

                    ProductVariantOption::query()->create([
                        'product_variant_id' => $variant->id,
                        'product_option_id' => $colorOption->id,
                        'product_option_value_id' => $colorValue->id,
                    ]);

                    $isFirstVariant = false;
                }
            }
        });
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ProductType::Digital,
            'track_stock' => false,
            'stock' => null,
            'in_stock' => true,
            'weight' => null,
            'weight_unit' => null,
            'length' => null,
            'width' => null,
            'height' => null,
            'dimension_unit' => null,
        ]);
    }
}
