<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WeightUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
#[UseModel(OrderItem::class)]
final class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 100);
        $totalPrice = round($unitPrice * $quantity, 2);
        $costPerItem = round($unitPrice * 0.6, 2);
        $taxAmount = round($totalPrice * 0.1, 2);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_title' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'cost_per_item' => $costPerItem,
            'tax_amount' => $taxAmount,
            'weight' => fake()->optional()->randomFloat(2, 0.1, 5),
            'weight_unit' => fake()->optional()->randomElement(WeightUnit::cases()),
            'requires_shipping' => true,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order->id,
        ]);
    }

    public function forProduct(Product $product): static
    {
        $variant = $product->variants()->inRandomOrder()->first();

        $variantOptions = [];

        if ($variant) {
            foreach ($variant->options as $variantOption) {
                $variantOptions[$variantOption->option->name] = $variantOption->value->value;
            }
        }

        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'media_id' => ($variant->media ?? $product->featured_media)?->id,
            'product_title' => $product->getTranslations('title'),
            'product_sku' => $product->sku,
            'variant_title' => $variant?->title,
            'variant_options' => $variantOptions,
            'cost_per_item' => $variant?->cost_per_item ?? $product->cost_per_item ?? round((float) $attributes['unit_price'] * 0.6, 2),
            'weight' => $variant->weight ?? $product->weight,
            'weight_unit' => ($variant->weight_unit ?? $product->weight_unit)?->value,
            'length' => $variant->length ?? $product->length,
            'width' => $variant->width ?? $product->width,
            'height' => $variant->height ?? $product->height,
            'dimension_unit' => ($variant->dimension_unit ?? $product->dimension_unit)?->value,
        ]);
    }
}
