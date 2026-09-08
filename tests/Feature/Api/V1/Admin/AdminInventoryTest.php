<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\StockMovementReason;
use App\Http\Controllers\Api\V1\Admin\InventoryController;
use App\Http\Requests\Admin\IndexInventoryRequest;
use App\Http\Requests\Admin\ShowInventoryRequest;
use App\Http\Resources\Api\V1\Admin\InventoryProductResource;
use App\Http\Resources\Api\V1\Admin\InventoryVariantResource;
use App\Http\Resources\Api\V1\Admin\StockMovementResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(
    InventoryController::class,
    IndexInventoryRequest::class,
    ShowInventoryRequest::class,
    InventoryProductResource::class,
    InventoryVariantResource::class,
    StockMovementResource::class,
);

uses()->group('api', 'admin-api');

test('the inventory list returns paginated stock levels', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    $product = Product::factory()->create([
        'title' => 'Copper Kettle',
        'sku' => 'KETTLE-1',
        'track_stock' => true,
        'stock' => 12,
        'low_stock_threshold' => 3,
        'in_stock' => true,
    ]);

    $response = getJson('/api/v1/admin/inventory?per_page=1')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'title', 'sku', 'stock', 'total_stock', 'in_stock', 'is_low_stock', 'variants']],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);

    expect($response->json('data.0.id'))->toBe($product->id)
        ->and($response->json('data.0.title'))->toBe('Copper Kettle')
        ->and($response->json('data.0.total_stock'))->toBe(12)
        ->and($response->json('data.0.is_low_stock'))->toBeFalse()
        ->and($response->json('meta.per_page'))->toBe(1);
});

test('the inventory list can be filtered to low stock products', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    $low = Product::factory()->create([
        'track_stock' => true,
        'stock' => 1,
        'low_stock_threshold' => 5,
        'in_stock' => true,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 90,
        'low_stock_threshold' => 5,
        'in_stock' => true,
    ]);

    $response = getJson('/api/v1/admin/inventory?low_stock=1')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($low->id)
        ->and($response->json('data.0.is_low_stock'))->toBeTrue();
});

test('the inventory list can be searched by sku', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    $match = Product::factory()->create(['sku' => 'FIND-ME-99']);
    Product::factory()->create(['sku' => 'OTHER-11']);

    $response = getJson('/api/v1/admin/inventory?query=FIND-ME')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($match->id);
});

test('the inventory list rejects an out of range page size', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    getJson('/api/v1/admin/inventory?per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

test('the inventory list is closed to staff without the permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    getJson('/api/v1/admin/inventory')->assertForbidden();
});

test('the inventory list is closed to customer tokens', function (): void {
    actingAsApiCustomer();

    getJson('/api/v1/admin/inventory')->assertForbidden();
});

test('the inventory history returns the movements of a product with its variants', function (): void {
    $user = actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    $product = Product::factory()->create(['track_stock' => true, 'stock' => 4]);
    $variant = ProductVariant::factory()->for($product)->create(['track_stock' => true, 'stock' => 2]);

    StockMovement::factory()->forProduct($product)->byUser($user)->received()->create([
        'quantity' => 4,
        'quantity_before' => 0,
        'quantity_after' => 4,
        'notes' => 'First delivery',
    ]);

    $response = getJson("/api/v1/admin/inventory/{$product->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'quantity', 'quantity_before', 'quantity_after', 'reason', 'notes', 'user', 'created_at']],
            'product' => ['id', 'title', 'variants'],
            'variant',
            'meta' => ['total'],
        ]);

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.reason'))->toBe(StockMovementReason::Received->value)
        ->and($response->json('data.0.user.id'))->toBe($user->id)
        ->and($response->json('product.id'))->toBe($product->id)
        ->and($response->json('product.variants.0.id'))->toBe($variant->id)
        ->and($response->json('variant'))->toBeNull();
});

test('the inventory history can be scoped to a single variant', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    $product = Product::factory()->create(['track_stock' => true, 'stock' => 0]);
    $variant = ProductVariant::factory()->for($product)->create(['track_stock' => true, 'stock' => 6]);

    StockMovement::factory()->forProduct($product)->create([
        'quantity' => 3,
        'quantity_before' => 0,
        'quantity_after' => 3,
    ]);

    StockMovement::factory()->forVariant($variant)->create([
        'quantity' => 6,
        'quantity_before' => 0,
        'quantity_after' => 6,
    ]);

    $response = getJson("/api/v1/admin/inventory/{$product->id}?variant={$variant->id}")->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.product_variant_id'))->toBe($variant->id)
        ->and($response->json('variant.id'))->toBe($variant->id)
        ->and($response->json('variant.stock'))->toBe(6);
});

test('the inventory history 404s for an unknown product', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    getJson('/api/v1/admin/inventory/999999')->assertNotFound();
});

test('adjusting a product that does not track stock fails validation', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryManage]));

    $product = Product::factory()->create(['track_stock' => false]);

    postJson('/api/v1/admin/inventory/stock-adjustments', [
        'product_id' => $product->id,
        'quantity' => 5,
        'reason' => StockMovementReason::Received->value,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');
});
