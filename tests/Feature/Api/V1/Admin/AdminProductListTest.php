<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\ProductSearchController;
use App\Http\Requests\Api\V1\Admin\SearchProductRequest;
use App\Http\Resources\Api\V1\Admin\AdminProductListResource;
use App\Http\Resources\Api\V1\Admin\AdminProductSearchResource;
use App\Models\Category;
use App\Models\Product;

use function Pest\Laravel\getJson;

covers(
    ProductController::class,
    ProductSearchController::class,
    SearchProductRequest::class,
    AdminProductListResource::class,
    AdminProductSearchResource::class,
);

uses()->group('api', 'admin-api');

test('the product list is paginated', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    Product::factory()->count(3)->create();

    getJson('/api/v1/admin/products?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure([
            'data' => [['id', 'title', 'price', 'price_range', 'total_stock', 'is_active', 'category', 'featured_media']],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

test('the product list resolves the localized title and category', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    $category = Category::factory()->create(['name' => ['en' => 'Footwear']]);
    Product::factory()->create([
        'title' => ['en' => 'Running Shoes'],
        'category_id' => $category->id,
    ]);

    getJson('/api/v1/admin/products')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Running Shoes')
        ->assertJsonPath('data.0.category.name', 'Footwear');
});

test('the product list can be filtered by search term and status', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    Product::factory()->create(['title' => 'Blue Hoodie', 'is_active' => true]);
    Product::factory()->create(['title' => 'Red Cap', 'is_active' => true]);
    Product::factory()->create(['title' => 'Blue Socks', 'is_active' => false]);

    getJson('/api/v1/admin/products?query=Blue')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    getJson('/api/v1/admin/products?query=Blue&is_active=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Blue Hoodie');
});

test('an invalid per page value is rejected', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    getJson('/api/v1/admin/products?per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

test('listing products requires the products view permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    getJson('/api/v1/admin/products')->assertForbidden();
});

test('a customer token cannot list products', function (): void {
    actingAsApiCustomer();

    getJson('/api/v1/admin/products')->assertForbidden();
});

test('the product search returns matching active products', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsReference]));

    Product::factory()->create(['title' => 'Wool Scarf', 'is_active' => true]);
    Product::factory()->create(['title' => 'Wool Gloves', 'is_active' => false]);

    getJson('/api/v1/admin/products/search?query=Wool')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Wool Scarf')
        ->assertJsonMissingPath('data.0.variants');
});

test('the product search can include variants', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsReference]));

    $product = Product::factory()->withVariants()->create(['title' => 'Cotton Tee', 'is_active' => true]);

    $response = getJson('/api/v1/admin/products/search?query=Cotton&with_variants=1')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->id);

    expect($response->json('data.0.variants'))->not->toBeEmpty()
        ->and($response->json('data.0.variants.0.options.0.name'))->toBeString();
});

test('searching products requires the products reference permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    getJson('/api/v1/admin/products/search')->assertForbidden();
});
