<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\SyncProductRelationsAction;
use App\Enums\ProductRelationType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
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

        $this->seedRelations();
    }

    private function seedRelations(): void
    {
        $products = Product::query()->get();

        if ($products->count() < 4) {
            return;
        }

        $action = app(SyncProductRelationsAction::class);

        foreach ($products->take(4) as $product) {
            $others = $products->reject(fn (Product $other): bool => $other->id === $product->id);

            $action->handle($product, ProductRelationType::CrossSell, $this->pickIds($others, 2));
            $action->handle($product, ProductRelationType::UpSell, $this->pickIds($others, 1));
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<int>
     */
    private function pickIds(Collection $products, int $count): array
    {
        return array_values(
            $products->shuffle()->take($count)->map(fn (Product $product): int => $product->id)->all()
        );
    }
}
