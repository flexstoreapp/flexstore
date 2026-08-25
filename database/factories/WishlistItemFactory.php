<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WishlistItem>
 */
final class WishlistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'product_id' => Product::factory(),
        ];
    }
}
