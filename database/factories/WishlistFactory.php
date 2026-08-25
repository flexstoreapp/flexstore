<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Wishlist>
 */
#[UseModel(Wishlist::class)]
final class WishlistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => null,
        ];
    }
}
