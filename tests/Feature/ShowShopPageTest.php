<?php

declare(strict_types=1);

use App\Filters\Criteria\MultipleCategoriesWithDescendantsCriterion;
use App\Filters\Criteria\OnSaleCriterion;
use App\Filters\Criteria\RatingCriterion;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers([
    ShopController::class,
    ProductFilterRequest::class,
    RatingCriterion::class,
    OnSaleCriterion::class,
    MultipleCategoriesWithDescendantsCriterion::class,
]);

uses()->group('product-list');

test('displays the shop page with products, facets and filters', function () {
    Product::factory()->count(3)->active()->create();

    get(route('shop.index'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/shop/list')
                ->has('products.data', 3)
                ->has('facets.categories')
                ->has('facets.brands')
                ->has('filters')
                ->where('filters.sort', 'latest')
        );
});

test('renders shop seo settings in the document head', function () {
    Setting::setValue('seo_shop_meta_title', 'All Products');
    Setting::setValue('seo_shop_meta_description', 'Browse the full catalog.');

    get(route('shop.index'))
        ->assertOk()
        ->assertSee('<title data-inertia="title">All Products</title>', false)
        ->assertSee('name="description"', false)
        ->assertSee('content="Browse the full catalog."', false);
});

test('omits the meta description when no shop meta description is set', function () {
    Setting::setValue('store_name', 'Flex');
    Setting::setValue('seo_shop_meta_title', null);
    Setting::setValue('seo_shop_meta_description', null);

    get(route('shop.index'))
        ->assertOk()
        ->assertSee('<title data-inertia="title">Shop - Flex</title>', false)
        ->assertDontSee('name="description"', false);
});

test('exposes category and brand facets with counts', function () {
    $category = Category::factory()->active()->create();
    $brand = Brand::factory()->active()->create();
    Product::factory()->count(2)->active()->create(['category_id' => $category->id, 'brand_id' => $brand->id]);

    get(route('shop.index'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('facets.categories.0.id', $category->id)
                ->where('facets.categories.0.count', 2)
                ->where('facets.brands.0.id', $brand->id)
                ->where('facets.brands.0.count', 2)
        );
});

test('filters by multiple categories including their descendants', function () {
    $electronics = Category::factory()->active()->create();
    $phones = Category::factory()->active()->create(['parent_id' => $electronics->id]);
    $fashion = Category::factory()->active()->create();
    $other = Category::factory()->active()->create();

    Product::factory()->active()->create(['category_id' => $phones->id]);
    Product::factory()->active()->create(['category_id' => $fashion->id]);
    Product::factory()->active()->create(['category_id' => $other->id]);

    get(route('shop.index', ['categories' => "{$electronics->id},{$fashion->id}"]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('products.data', 2));
});

test('filters by minimum rating', function () {
    $highlyRated = Product::factory()->active()->create();
    $poorlyRated = Product::factory()->active()->create();

    Review::factory()->approved()->create(['product_id' => $highlyRated->id, 'rating' => 5]);
    Review::factory()->approved()->create(['product_id' => $poorlyRated->id, 'rating' => 2]);

    get(route('shop.index', ['rating' => 4]))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $highlyRated->id)
                ->where('filters.rating', fn (mixed $rating): bool => (float) $rating === 4.0)
        );
});

test('filters by on sale products', function () {
    $onSale = Product::factory()->active()->create(['price' => 50, 'compare_at_price' => 80]);
    Product::factory()->active()->create(['price' => 50, 'compare_at_price' => null]);

    get(route('shop.index', ['on_sale' => 1]))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $onSale->id)
                ->where('filters.on_sale', true)
        );
});

test('rejects a rating above 5', function () {
    get(route('shop.index', ['rating' => 6]))->assertInvalid(['rating']);
});

test('limits results to the selected per page count', function () {
    Product::factory()->count(40)->active()->create();

    get(route('shop.index', ['per_page' => 36]))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('products.data', 36)
                ->where('filters.per_page', 36)
        );
});

test('rejects a per page value outside the allowed options', function () {
    get(route('shop.index', ['per_page' => 13]))->assertInvalid(['per_page']);
});
