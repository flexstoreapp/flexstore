<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CartItem>
 */
final class CartItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 200);
        $totalPrice = round($quantity * $unitPrice, 2);

        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => $quantity,
            'unit_price' => number_format($unitPrice, 4, '.', ''),
            'compare_at_price' => null,
            'total_price' => number_format($totalPrice, 4, '.', ''),
            'variant_title' => null,
            'variant_options' => null,
        ];
    }
}
