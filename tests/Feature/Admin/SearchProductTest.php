<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\ProductSearchController;
use App\Http\Requests\Admin\ProductSearchRequest;
use App\Models\Category;
use App\Models\Product;
use App\Queries\ProductSearchQuery;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

covers(ProductSearchController::class, ProductSearchRequest::class, ProductSearchQuery::class);

uses()->group('filters', 'product');

test('returns products without filters', function () {
    Product::factory()->count(20)->create();

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'title', 'price']],
            'next_page_url',
        ]);
});

test('returns products filtered by query term', function () {
    Product::factory()->active()->create(['title' => 'Test Product']);
    Product::factory()->active()->create(['title' => 'Another Product']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['query' => 'Test']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Test Product'));
});

test('returns products filtered by SKU', function () {
    Product::factory()->active()->create(['title' => 'Product 1', 'sku' => 'TEST123']);
    Product::factory()->active()->create(['title' => 'Product 2', 'sku' => 'ABC456']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['query' => 'TEST']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Product 1'));
});

test('returns products filtered by URL handle', function () {
    Product::factory()->active()->create(['title' => 'Product 1', 'url_handle' => 'product-one']);
    Product::factory()->active()->create(['title' => 'Product 2', 'url_handle' => 'product-two']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['query' => 'product-one']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Product 1'));
});

test('returns products filtered by category', function () {
    $category = Category::factory()->create(['name' => 'Test Category', 'url_handle' => 'test-category']);

    Product::factory()->active()->create(['title' => 'Product in Category', 'category_id' => $category->id]);
    Product::factory()->active()->create(['title' => 'Product without Category']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['category' => $category->id]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Product in Category'));
});

test('returns products with minimum price', function () {
    Product::factory()->active()->create(['title' => 'Cheap Product', 'price' => '10.0000']);
    Product::factory()->active()->create(['title' => 'Expensive Product', 'price' => '100.0000']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['min_price' => '50']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Expensive Product'));
});

test('returns products with maximum price', function () {
    Product::factory()->active()->create(['title' => 'Cheap Product', 'price' => '10.0000']);
    Product::factory()->active()->create(['title' => 'Expensive Product', 'price' => '100.0000']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['max_price' => '50']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Cheap Product'));
});

test('returns in-stock products when in_stock filter is true', function () {
    Product::factory()->active()->create(['title' => 'In Stock Product', 'in_stock' => true]);
    Product::factory()->active()->create(['title' => 'Out of Stock Product', 'in_stock' => false]);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['in_stock' => 'true']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('In Stock Product'));
});

test('returns out-of-stock products when in_stock filter is false', function () {
    Product::factory()->active()->create(['title' => 'In Stock Product', 'in_stock' => true]);
    Product::factory()->active()->create(['title' => 'Out of Stock Product', 'in_stock' => false]);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', ['in_stock' => 'false']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Out of Stock Product'));
});

test('sorts products by title in ascending order', function () {
    Product::factory()->active()->create(['title' => 'Z Product']);
    Product::factory()->active()->create(['title' => 'A Product']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'sort' => 'title',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('A Product'))
        ->assertJsonPath('data.1.title', castAsTranslatableArray('Z Product'));
});

test('sorts products by title in descending order', function () {
    Product::factory()->active()->create(['title' => 'A Product']);
    Product::factory()->active()->create(['title' => 'Z Product']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'sort' => 'title',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Z Product'))
        ->assertJsonPath('data.1.title', castAsTranslatableArray('A Product'));
});

test('sorts products by price in ascending order', function () {
    Product::factory()->active()->create(['title' => 'Expensive Product', 'price' => '100.0000']);
    Product::factory()->active()->create(['title' => 'Cheap Product', 'price' => '10.0000']);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'sort' => 'price',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Cheap Product'))
        ->assertJsonPath('data.1.title', castAsTranslatableArray('Expensive Product'));
});

test('sorts products by stock in ascending order', function () {
    Product::factory()->active()->create(['title' => 'High Stock Product', 'stock' => 100]);
    Product::factory()->active()->create(['title' => 'Low Stock Product', 'stock' => 10]);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'sort' => 'stock',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('Low Stock Product'))
        ->assertJsonPath('data.1.title', castAsTranslatableArray('High Stock Product'));
});

test('sorts products by stock in descending order', function () {
    Product::factory()->active()->create(['title' => 'Low Stock Product', 'stock' => 10]);
    Product::factory()->active()->create(['title' => 'High Stock Product', 'stock' => 100]);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'sort' => 'stock',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('High Stock Product'))
        ->assertJsonPath('data.1.title', castAsTranslatableArray('Low Stock Product'));
});

test('sorts products with variants by minimum variant price in ascending order', function () {
    $productWithVariants = Product::factory()->active()->create([
        'title' => 'Product With Variants',
        'price' => null,
        'stock' => 0,
    ]);

    $productWithVariants->variants()->create([
        'title' => 'Variant 1',
        'price' => '50.0000',
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $productWithVariants->variants()->create([
        'title' => 'Variant 2',
        'price' => '30.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    Product::factory()->active()->create([
        'title' => 'Regular Product',
        'price' => '40.0000',
        'stock' => 20,
    ]);

    Product::factory()->active()->create([
        'title' => 'Expensive Product',
        'price' => '100.0000',
        'stock' => 5,
    ]);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'sort' => 'price',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertJsonCount(3, 'data');

    // Product With Variants (effective price 30.00) should come first
    $data = $response->json('data');
    expect($data[0]['title'])->toEqual(castAsTranslatableArray('Product With Variants'))
        ->and($data[1]['title'])->toEqual(castAsTranslatableArray('Regular Product'))
        ->and($data[2]['title'])->toEqual(castAsTranslatableArray('Expensive Product'));
});

test('sorts products with variants by total stock in ascending order', function () {
    $productWithVariants = Product::factory()->active()->create([
        'title' => 'Product With Variants',
        'price' => null,
        'stock' => 0,
    ]);

    $productWithVariants->variants()->create([
        'title' => 'Variant 1',
        'price' => '50.0000',
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $productWithVariants->variants()->create([
        'title' => 'Variant 2',
        'price' => '30.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    Product::factory()->active()->create([
        'title' => 'Regular Product',
        'price' => '40.0000',
        'stock' => 20,
    ]);

    Product::factory()->active()->create([
        'title' => 'Low Stock Product',
        'price' => '25.0000',
        'stock' => 5,
    ]);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'sort' => 'stock',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertJsonCount(3, 'data');

    // Expected order: Low Stock Product (5), Product With Variants (15), Regular Product (20)
    $data = $response->json('data');
    expect($data[0]['title'])->toEqual(castAsTranslatableArray('Low Stock Product'))
        ->and($data[1]['title'])->toEqual(castAsTranslatableArray('Product With Variants'))
        ->and($data[2]['title'])->toEqual(castAsTranslatableArray('Regular Product'));
});

test('combines multiple filters correctly', function () {
    Product::factory()->active()->create([
        'title' => 'Old Active Product',
        'price' => '50',
    ]);

    Product::factory()->active()->create([
        'title' => 'New Active Product',
        'price' => '75',
    ]);

    Product::factory()->inactive()->create([
        'title' => 'New Inactive Product',
        'price' => '100',
    ]);

    $response = actingAsSuperAdmin()->getJson(route('admin.products.search', [
        'min_price' => '60',
        'sort' => 'title',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', castAsTranslatableArray('New Active Product'));
});

test('requires authentication', function () {
    Product::factory()->count(2)->create();

    $response = getJson(route('admin.products.search'));

    $response->assertUnauthorized();
});

test('requires product reference access', function () {
    Product::factory()->active()->count(2)->create();

    actingAs(userWithPermissions([Permission::ProductsView]))
        ->getJson(route('admin.products.search'))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    actingAs(userWithPermissions([Permission::DashboardView]))
        ->getJson(route('admin.products.search'))
        ->assertForbidden();
});
