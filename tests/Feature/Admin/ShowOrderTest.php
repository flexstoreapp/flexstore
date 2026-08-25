<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\OrderController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDownload;
use App\Models\User;
use App\Queries\CustomerStatsByEmailQuery;
use App\Queries\OrderItemBreakdownQuery;
use Inertia\Testing\AssertableInertia as Assert;

covers(OrderController::class, OrderItemBreakdownQuery::class, CustomerStatsByEmailQuery::class);

uses()->group('admin');

test('order show page can be rendered', function () {
    $order = Order::factory()->create();

    actingAsSuperAdmin()
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->has('order'));
});

test('order show page includes customer stats when customer exists', function () {
    $customer = User::factory()->create(['lifetime_value' => '150.0000']);
    Order::factory()->count(3)->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'total' => '50.0000',
    ]);

    $order = Order::query()->where('customer_id', $customer->id)->first();

    actingAsSuperAdmin()
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->where('customerStats.name', $customer->name)
            ->where('customerStats.orders_count', 3)
            ->where('customerStats.lifetime_value', '150.0000'));
});

test('order show page aggregates stats by email for guest orders', function () {
    Order::factory()->count(2)->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    $order = Order::query()->where('customer_email', 'guest@example.com')->first();

    actingAsSuperAdmin()
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->where('customerStats.name', null)
            ->where('customerStats.orders_count', 2));
});

test('order show page includes digital download grants', function () {
    $order = Order::factory()->paid()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'requires_shipping' => false]);
    $download = OrderItemDownload::factory()->create([
        'order_id' => $order->id,
        'order_item_id' => $item->id,
        'download_count' => 2,
    ]);

    actingAsSuperAdmin()
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders/show')
            ->has('order.item_downloads', 1)
            ->where('order.item_downloads.0.id', $download->id)
            ->where('order.item_downloads.0.download_count', 2)
            ->where('order.item_downloads.0.is_available', true));
});
