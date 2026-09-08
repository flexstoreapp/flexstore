<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\BrandProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CategoryProductController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductFacetController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

use function Pest\Laravel\getJson;

covers(
    ProductController::class,
    ProductFacetController::class,
    CategoryController::class,
    CategoryProductController::class,
    BrandController::class,
    BrandProductController::class,
);

uses()->group('api');

test('the product list returns paginated cards with the title as a plain string', function (): void {
    Product::factory()->create(['is_active' => true, 'title' => 'Merino crew knit']);

    getJson(route('api.v1.shop.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'url_handle', 'title', 'price', 'in_stock', 'has_variants']],
            'meta' => ['current_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('data.0.title', 'Merino crew knit');
});

test('the product list excludes inactive products', function (): void {
    Product::factory()->create(['is_active' => false]);

    getJson(route('api.v1.shop.index'))->assertOk()->assertJsonCount(0, 'data');
});

test('a product is fetched by its url handle', function (): void {
    $product = Product::factory()->create(['is_active' => true]);

    getJson(route('api.v1.products.show', $product->url_handle))
        ->assertOk()
        ->assertJsonPath('id', $product->id)
        ->assertJsonStructure(['id', 'title', 'description', 'media', 'options', 'variants', 'rating_distribution']);
});

test('an inactive product is not exposed', function (): void {
    $product = Product::factory()->create(['is_active' => false]);

    getJson(route('api.v1.products.show', $product->url_handle))->assertNotFound();
});

test('facets are returned with a localized name and a count', function (): void {
    $category = Category::factory()->create(['is_active' => true, 'name' => 'Knitwear']);
    Product::factory()->create(['is_active' => true, 'category_id' => $category->id]);

    getJson(route('api.v1.shop.facets'))
        ->assertOk()
        ->assertJsonStructure(['categories', 'brands', 'price_buckets', 'rating_buckets'])
        ->assertJsonPath('categories.0.name', $category->name)
        ->assertJsonPath('categories.0.count', 1);
});

test('the category tree is returned with product counts', function (): void {
    $category = Category::factory()->create(['is_active' => true]);
    Product::factory()->create(['is_active' => true, 'category_id' => $category->id]);

    getJson(route('api.v1.categories.index'))
        ->assertOk()
        ->assertJsonPath('0.id', $category->id)
        ->assertJsonPath('0.product_count', 1);
});

test('products can be listed for a category', function (): void {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true, 'category_id' => $category->id]);
    Product::factory()->create(['is_active' => true]);

    getJson(route('api.v1.categories.products.index', $category->url_handle))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product->id);
});

test('an inactive category is not exposed', function (): void {
    $category = Category::factory()->create(['is_active' => false]);

    getJson(route('api.v1.categories.products.index', $category->url_handle))->assertNotFound();
});

test('products can be listed for a brand', function (): void {
    $brand = Brand::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true, 'brand_id' => $brand->id]);
    Product::factory()->create(['is_active' => true]);

    getJson(route('api.v1.brands.products.index', $brand->url_handle))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product->id);
});

test('the accept-language header selects the locale of translated fields', function (): void {
    Setting::setValue('available_locales', ['en', 'ar']);
    Setting::setValue('default_locale', 'en');

    $product = Product::factory()->create(['is_active' => true]);
    $product->setTranslation('title', 'en', 'Merino crew knit')
        ->setTranslation('title', 'ar', 'سترة ميرينو')
        ->save();

    getJson(route('api.v1.products.show', $product->url_handle), ['Accept-Language' => 'ar'])
        ->assertOk()
        ->assertJsonPath('title', 'سترة ميرينو');

    getJson(route('api.v1.products.show', $product->url_handle), ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertJsonPath('title', 'Merino crew knit');
});

test('the category tree nests children under their parent', function (): void {
    $parent = Category::factory()->create(['is_active' => true]);
    $child = Category::factory()->create(['is_active' => true, 'parent_id' => $parent->id]);
    Product::factory()->create(['is_active' => true, 'category_id' => $child->id]);

    getJson(route('api.v1.categories.index'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $parent->id)
        ->assertJsonPath('0.children.0.id', $child->id)
        ->assertJsonPath('0.product_count', 1);
});
