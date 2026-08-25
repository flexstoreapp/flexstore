<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\StockMovementReason;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Requests\Admin\ShowInventoryRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Queries\StockMovementListQuery;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(InventoryController::class, ShowInventoryRequest::class, StockMovementListQuery::class);

uses()->group('inventory');

test('displays inventory history page for a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
        'user_id' => $user->id,
        'quantity' => 5,
        'quantity_before' => 5,
        'quantity_after' => 10,
        'reason' => StockMovementReason::Received,
        'notes' => 'Received stock',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->has('product')
                ->where('product.id', $product->id)
                ->where('variant', null)
                ->has('stockMovements.data', 1)
                ->where('stockMovements.data.0.quantity', 5)
                ->where('stockMovements.data.0.reason', StockMovementReason::Received->value)
                ->where('stockMovements.data.0.notes', 'Received stock')
                ->has('stockMovements.data.0.user')
                ->where('stockMovements.data.0.user.id', $user->id)
        );
});

test('displays inventory history page for a product variant', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 15,
    ]);

    StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'user_id' => $user->id,
        'quantity' => -3,
        'quantity_before' => 18,
        'quantity_after' => 15,
        'reason' => StockMovementReason::Sale,
        'notes' => 'Sale transaction',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', [
        'product' => $product,
        'variant' => $variant->id,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->has('product')
                ->where('product.id', $product->id)
                ->has('variant')
                ->where('variant.id', $variant->id)
                ->has('stockMovements.data', 1)
                ->where('stockMovements.data.0.quantity', -3)
                ->where('stockMovements.data.0.reason', StockMovementReason::Sale->value)
                ->where('stockMovements.data.0.product_variant_id', $variant->id)
        );
});

test('displays multiple stock movements in chronological order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 20,
    ]);

    $movement1 = StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
        'user_id' => $user->id,
        'quantity' => 10,
        'quantity_before' => 0,
        'quantity_after' => 10,
        'reason' => StockMovementReason::Received,
        'created_at' => now()->subDays(2),
    ]);

    $movement2 = StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
        'user_id' => $user->id,
        'quantity' => 5,
        'quantity_before' => 10,
        'quantity_after' => 15,
        'reason' => StockMovementReason::Manual,
        'created_at' => now()->subDay(),
    ]);

    $movement3 = StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
        'user_id' => $user->id,
        'quantity' => 5,
        'quantity_before' => 15,
        'quantity_after' => 20,
        'reason' => StockMovementReason::Received,
        'created_at' => now(),
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->has('stockMovements.data', 3)
                ->where('stockMovements.data.0.id', $movement3->id)
                ->where('stockMovements.data.1.id', $movement2->id)
                ->where('stockMovements.data.2.id', $movement1->id)
        );
});

test('paginates stock movements', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
    ]);

    foreach (range(1, 25) as $i) {
        StockMovement::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'user_id' => $user->id,
            'quantity' => 1,
            'quantity_before' => $i - 1,
            'quantity_after' => $i,
            'reason' => StockMovementReason::Manual,
            'created_at' => now()->subMinutes(25 - $i),
        ]);
    }

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->has('stockMovements.data', 15)
                ->where('stockMovements.total', 25)
                ->where('stockMovements.current_page', 1)
                ->where('stockMovements.per_page', 15)
                ->where('stockMovements.last_page', 2)
        );
});

test('handles stock movements without user', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 5,
    ]);

    StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => null,
        'user_id' => null,
        'quantity' => 5,
        'quantity_before' => 0,
        'quantity_after' => 5,
        'reason' => StockMovementReason::Manual,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->where('stockMovements.data.0.user', null)
        );
});

test('only shows stock movements for the specified product', function () {
    $user = User::factory()->create();
    $product1 = Product::factory()->create(['track_stock' => true]);
    $product2 = Product::factory()->create(['track_stock' => true]);

    StockMovement::query()->create([
        'product_id' => $product1->id,
        'product_variant_id' => null,
        'user_id' => $user->id,
        'quantity' => 10,
        'quantity_before' => 0,
        'quantity_after' => 10,
        'reason' => StockMovementReason::Received,
    ]);

    StockMovement::query()->create([
        'product_id' => $product2->id,
        'product_variant_id' => null,
        'user_id' => $user->id,
        'quantity' => 5,
        'quantity_before' => 0,
        'quantity_after' => 5,
        'reason' => StockMovementReason::Received,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', $product1));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->has('stockMovements.data', 1)
                ->where('stockMovements.data.0.product_id', $product1->id)
        );
});

test('only shows stock movements for the specified variant', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
    ]);

    StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant1->id,
        'user_id' => $user->id,
        'quantity' => 10,
        'quantity_before' => 0,
        'quantity_after' => 10,
        'reason' => StockMovementReason::Received,
    ]);

    StockMovement::query()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant2->id,
        'user_id' => $user->id,
        'quantity' => 5,
        'quantity_before' => 0,
        'quantity_after' => 5,
        'reason' => StockMovementReason::Received,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', [
        'product' => $product,
        'variant' => $variant1->id,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->has('stockMovements.data', 1)
                ->where('stockMovements.data.0.product_variant_id', $variant1->id)
        );
});

test('shows product history when variant does not belong to product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product2->id,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', [
        'product' => $product1,
        'variant' => $variant->id,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->where('variant', null)
        );
});

test('displays empty state when no stock movements exist', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.inventory.show', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/inventory/history')
                ->has('product')
                ->where('variant', null)
                ->has('stockMovements.data', 0)
                ->where('stockMovements.total', 0)
        );
});

test('requires authentication', function () {
    $product = Product::factory()->create();

    $response = get(route('admin.inventory.show', $product));

    $response->assertRedirect(route('admin.login'));
});

test('requires inventory.view permission', function () {
    $role = Spatie\Permission\Models\Role::query()->where(['name' => App\Enums\Role::Admin])->firstOrFail();
    $product = Product::factory()->create();

    $response = actingAsAdmin()->get(route('admin.inventory.show', $product));

    $response->assertOk();

    $role->revokePermissionTo(Permission::InventoryView);

    $response = actingAsAdmin()->get(route('admin.inventory.show', $product));

    $response->assertForbidden();
});
