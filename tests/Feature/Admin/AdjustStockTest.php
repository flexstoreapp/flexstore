<?php

declare(strict_types=1);

use App\Actions\AdjustStockAction;
use App\Enums\Permission;
use App\Enums\StockMovementReason;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;

covers(StockAdjustmentController::class, StoreStockAdjustmentRequest::class, AdjustStockAction::class);

uses()->group('inventory');

test('can adjust stock for a product', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.inventory.stock-adjustments.store'), [
        'product_id' => $product->id,
        'quantity' => 5,
        'reason' => StockMovementReason::Manual->value,
        'notes' => 'Test adjustment',
    ]);

    $response->assertRedirect();

    expect($product->fresh()->stock)->toBe(15)
        ->and(StockMovement::query()->where('product_id', $product->id)->count())->toBe(1);
});

test('can adjust stock for a product variant', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.inventory.stock-adjustments.store'), [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 7,
        'reason' => StockMovementReason::Manual->value,
    ]);

    $response->assertRedirect();

    expect($variant->fresh()->stock)->toBe(10);
});

test('can decrease stock for a product', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.inventory.stock-adjustments.store'), [
        'product_id' => $product->id,
        'quantity' => -3,
        'reason' => StockMovementReason::Manual->value,
    ]);

    $response->assertRedirect();

    expect($product->fresh()->stock)->toBe(7);
});

test('validates required fields when adjusting stock', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.inventory.stock-adjustments.store'), []);

    $response->assertSessionHasErrors(['product_id', 'quantity', 'reason']);
});

test('cannot adjust stock for product that does not track stock', function () {
    $product = Product::factory()->create([
        'track_stock' => false,
        'stock' => null,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.inventory.stock-adjustments.store'), [
        'product_id' => $product->id,
        'quantity' => 5,
        'reason' => StockMovementReason::Manual->value,
    ]);

    $response->assertStatus(500);
});

test('requires inventory.manage permission', function () {
    $role = Spatie\Permission\Models\Role::query()->where(['name' => App\Enums\Role::Admin])->firstOrFail();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $role->revokePermissionTo(Permission::InventoryManage);

    $response = actingAsAdmin()->post(route('admin.inventory.stock-adjustments.store'), [
        'product_id' => $product->id,
        'quantity' => 5,
        'reason' => StockMovementReason::Manual->value,
    ]);

    $response->assertForbidden();
});

test('cannot adjust stock for base product when variants exist', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 5,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.inventory.stock-adjustments.store'), [
        'product_id' => $product->id,
        'quantity' => 5,
        'reason' => StockMovementReason::Manual->value,
    ]);

    $response->assertStatus(500);
    expect($product->fresh()->stock)->toBe(10);
});
