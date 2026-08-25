<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

final class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        Review::factory(10)
            ->recycle(Product::all())
            ->recycle(Order::all())
            ->create();
    }
}
