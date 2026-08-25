<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Requests\Admin\IndexInventoryRequest;
use App\Models\Product;
use App\Models\Setting;
use App\Queries\InventoryListQuery;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(InventoryController::class, InventoryListQuery::class, IndexInventoryRequest::class);

uses()->group('inventory');

test('displays inventory list page', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
                ->where('filters.query', null)
                ->where('filters.low_stock', null)
                ->where('filters.in_stock', null)
        );
});

test('can filter inventory by low stock', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'low_stock' => true,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
        );
});

test('can filter inventory by stock status', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'in_stock' => true,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
        );
});

test('can filter inventory by query search', function () {
    Product::factory()->create([
        'title' => 'Test Product',
        'sku' => 'TEST-001',
    ]);

    Product::factory()->create([
        'title' => 'Another Product',
        'sku' => 'ANOTHER-001',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'query' => 'test',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
        );
});

test('can filter inventory by URL handle in query search', function () {
    Product::factory()->create([
        'title' => 'Test Product',
        'url_handle' => 'test-product',
    ]);

    Product::factory()->create([
        'title' => 'Another Product',
        'url_handle' => 'another-product',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'query' => 'test-product',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
        );
});

test('can filter inventory by sku in query search', function () {
    Product::factory()->create([
        'title' => 'Test Product',
        'sku' => 'TEST-001',
    ]);

    Product::factory()->create([
        'title' => 'Another Product',
        'sku' => 'ANOTHER-001',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'query' => 'TEST-001',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
        );
});

test('can filter inventory by in stock with variants', function () {
    $product1 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $product2 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product3 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product3->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'in_stock' => true,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 2)
        );
});

test('can filter inventory by out of stock with variants', function () {
    $product1 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product2 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $product2->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'in_stock' => false,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 2)
        );
});

test('filters variants when filtering by in stock', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $product->variants()->create([
        'title' => 'In Stock Variant',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $product->variants()->create([
        'title' => 'Out of Stock Variant',
        'price' => '20.0000',
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'in_stock' => true,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
                ->where('products.data.0.variants', function ($variants) {
                    expect($variants)->toHaveCount(1)
                        ->and($variants[0]['title'])->toBe(castAsTranslatableArray('In Stock Variant'));

                    return true;
                })
        );
});

test('filters variants when filtering by out of stock', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product->variants()->create([
        'title' => 'In Stock Variant',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $product->variants()->create([
        'title' => 'Out of Stock Variant',
        'price' => '20.0000',
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'in_stock' => false,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
                ->where('products.data.0.variants', function ($variants) {
                    expect($variants)->toHaveCount(1)
                        ->and($variants[0]['title'])->toBe(castAsTranslatableArray('Out of Stock Variant'));

                    return true;
                })
        );
});

test('can combine multiple filters', function () {
    Setting::setValue('default_low_stock_threshold', 5);

    Product::factory()->create([
        'title' => 'Low Stock Product',
        'sku' => 'LOW-001',
        'track_stock' => true,
        'stock' => 3,
        'in_stock' => true,
    ]);

    Product::factory()->create([
        'title' => 'High Stock Product',
        'sku' => 'HIGH-001',
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.index', [
        'query' => 'low',
        'low_stock' => true,
        'in_stock' => true,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/list')
                ->has('products.data', 1)
        );
});

test('requires authentication', function () {
    $response = get(route('admin.inventory.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires inventory.view permission', function () {
    $role = Spatie\Permission\Models\Role::query()->where(['name' => App\Enums\Role::Admin])->firstOrFail();
    Product::factory()->create();

    $response = actingAsAdmin()->get(route('admin.inventory.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::InventoryView);

    $response = actingAsAdmin()->get(route('admin.inventory.index'));

    $response->assertForbidden();
});
