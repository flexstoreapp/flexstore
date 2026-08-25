<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

final class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->pluck('id');
        $brands = Brand::query()->pluck('id');

        Product::factory(5)->create([
            'category_id' => $categories->random(),
            'brand_id' => $brands->random(),
        ]);

        Product::factory(5)->withVariants()->create([
            'category_id' => $categories->random(),
            'brand_id' => $brands->random(),
        ]);
    }
}
