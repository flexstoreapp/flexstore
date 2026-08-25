<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\DuplicateOrderController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Queries\ActivePaymentGatewayListQuery;
use App\Queries\DuplicateOrderChangeQuery;
use App\Queries\DuplicateOrderSourceQuery;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers([
    DuplicateOrderController::class,
    DuplicateOrderSourceQuery::class,
    DuplicateOrderChangeQuery::class,
    ActivePaymentGatewayListQuery::class,
]);

uses()->group('admin', 'order');

test('duplicate order page renders with source order data', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->forProduct($product)->create();

    actingAsSuperAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/create')
            ->has('sourceOrder')
            ->has('sourceOrder.items', 1)
            ->has('sourceOrder.shipping_address')
            ->has('sourceOrderChanges')
            ->has('paymentGateways')
            ->has('displayTaxTotals'));
});

test('duplicate order filters out items with deleted products', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->forProduct($product)->create();
    OrderItem::factory()->forOrder($order)->create(['product_id' => null]);

    actingAsSuperAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/create')
            ->has('sourceOrder.items', 1));
});

test('duplicate order refreshes item snapshots from current product data', function () {
    $product = Product::factory()->create([
        'title' => 'Original title',
        'sku' => 'ORIG-SKU',
        'price' => '10.0000',
    ]);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['quantity' => 3, 'unit_price' => '10.0000', 'total_price' => '30.0000']);

    $product->update([
        'title' => 'Updated title',
        'sku' => 'NEW-SKU',
        'price' => '12.5000',
    ]);

    actingAsSuperAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sourceOrder.items.0.product_title.en', 'Updated title')
            ->where('sourceOrder.items.0.product_sku', 'NEW-SKU')
            ->where('sourceOrder.items.0.unit_price', '12.5000')
            ->where('sourceOrder.items.0.total_price', '37.5000')
            ->where('sourceOrder.subtotal', '37.5000'));
});

test('duplicate order includes changes when product data differs', function () {
    $product = Product::factory()->create([
        'title' => 'Original title',
        'price' => '10.0000',
    ]);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['unit_price' => '10.0000', 'product_sku' => $product->sku]);

    $product->update(['title' => 'Updated title']);

    actingAsSuperAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sourceOrderChanges', 1)
            ->where('sourceOrderChanges.0.type', 'updated'));
});

test('duplicate order includes removed change for deleted products', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->create(['product_id' => null]);

    actingAsSuperAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sourceOrderChanges', 1)
            ->where('sourceOrderChanges.0.type', 'removed'));
});

test('duplicate order returns empty changes when nothing changed', function () {
    $product = Product::factory()->create(['price' => '10.0000']);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['unit_price' => '10.0000', 'product_sku' => $product->sku]);

    actingAsSuperAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sourceOrderChanges', 0));
});

test('duplicate order requires orders.create permission', function () {
    $order = Order::factory()->create();

    actingAsSuperAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertOk();

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::OrdersManage);

    actingAsAdmin()
        ->get(route('admin.orders.duplicate', $order))
        ->assertForbidden();
});

test('duplicate order requires authentication', function () {
    $order = Order::factory()->create();

    get(route('admin.orders.duplicate', $order))
        ->assertRedirect(route('admin.login'));
});
