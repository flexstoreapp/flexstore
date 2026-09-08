<?php

declare(strict_types=1);

use App\Actions\AdjustStockAction;
use App\Enums\Permission;
use App\Enums\StockMovementReason;
use App\Http\Controllers\Api\V1\Admin\StockAdjustmentController;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

covers(StockAdjustmentController::class, StoreStockAdjustmentRequest::class, AdjustStockAction::class);

uses()->group('api', 'admin-api');

test('stock is adjusted for a product', function (): void {
    $user = actingAsApiAdmin(userWithPermissions([Permission::InventoryManage]));

    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);

    $response = postJson('/api/v1/admin/inventory/stock-adjustments', [
        'product_id' => $product->id,
        'quantity' => 5,
        'reason' => StockMovementReason::Manual->value,
        'notes' => 'Recount',
    ])->assertCreated();

    expect($response->json('quantity'))->toBe(5)
        ->and($response->json('quantity_before'))->toBe(10)
        ->and($response->json('quantity_after'))->toBe(15)
        ->and($response->json('reason'))->toBe(StockMovementReason::Manual->value)
        ->and($response->json('user.id'))->toBe($user->id)
        ->and($product->fresh()->stock)->toBe(15);

    assertDatabaseHas(StockMovement::class, [
        'product_id' => $product->id,
        'quantity' => 5,
        'notes' => 'Recount',
    ]);
});

test('stock is adjusted for a variant', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryManage]));

    $product = Product::factory()->create(['track_stock' => true, 'stock' => 0]);
    $variant = ProductVariant::factory()->for($product)->create(['track_stock' => true, 'stock' => 3]);

    $response = postJson('/api/v1/admin/inventory/stock-adjustments', [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => -1,
        'reason' => StockMovementReason::Damaged->value,
    ])->assertCreated();

    expect($response->json('product_variant_id'))->toBe($variant->id)
        ->and($variant->fresh()->stock)->toBe(2);
});

test('a stock adjustment is validated', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryManage]));

    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);

    postJson('/api/v1/admin/inventory/stock-adjustments', [
        'product_id' => $product->id,
        'quantity' => 0,
        'reason' => 'teleported',
    ])->assertUnprocessable()->assertJsonValidationErrors(['quantity', 'reason']);
});

test('a variant belonging to another product is rejected', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryManage]));

    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);
    $foreignVariant = ProductVariant::factory()->create(['track_stock' => true, 'stock' => 3]);

    postJson('/api/v1/admin/inventory/stock-adjustments', [
        'product_id' => $product->id,
        'product_variant_id' => $foreignVariant->id,
        'quantity' => 2,
        'reason' => StockMovementReason::Manual->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('product_variant_id');
});

test('read only staff cannot adjust stock', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::InventoryView]));

    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);

    postJson('/api/v1/admin/inventory/stock-adjustments', [
        'product_id' => $product->id,
        'quantity' => 5,
        'reason' => StockMovementReason::Manual->value,
    ])->assertForbidden();

    expect($product->fresh()->stock)->toBe(10);
});
