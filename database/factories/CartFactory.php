<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Cart>
 */
#[UseModel(Cart::class)]
final class CartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'coupon_code' => null,
            'subtotal' => '0.0000',
            'discount_total' => '0.0000',
            'shipping_total' => '0.0000',
            'tax_total' => '0.0000',
            'total' => '0.0000',
        ];
    }
}
