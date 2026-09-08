<?php

declare(strict_types=1);

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Requests\Admin\IndexAdminOrderRequest;
use App\Http\Resources\Api\V1\Admin\AdminOrderResource;
use App\Http\Resources\Api\V1\Admin\AdminOrderSummaryResource;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderAddress;
use App\Models\OrderItem;

use function Pest\Laravel\getJson;

covers(
    AdminOrderController::class,
    AdminOrderResource::class,
    AdminOrderSummaryResource::class,
    IndexAdminOrderRequest::class,
);

uses()->group('api', 'admin-api');

test('orders are listed with their summary fields', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->unfulfilled()->create(['customer_email' => 'buyer@example.test']);
    OrderAddress::factory()->billing()->create([
        'order_id' => $order->id,
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 3]);

    getJson('/api/v1/admin/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $order->id)
        ->assertJsonPath('data.0.customer_email', 'buyer@example.test')
        ->assertJsonPath('data.0.customer_name', 'Ada Lovelace')
        ->assertJsonPath('data.0.item_count', 3)
        ->assertJsonPath('data.0.is_canceled', false)
        ->assertJsonPath('data.0.fulfillment_status', FulfillmentStatus::Unfulfilled->value)
        ->assertJsonStructure(['data' => [['id', 'created_at', 'payment_status', 'currency_code', 'total']], 'meta' => ['total']]);
});

test('the order list is filtered by payment status', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $paid = Order::factory()->paid()->create();
    Order::factory()->unpaid()->create();

    getJson('/api/v1/admin/orders?payment_status=' . PaymentStatus::Paid->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $paid->id);
});

test('the order list is searchable by customer email', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $match = Order::factory()->create(['customer_email' => 'needle@example.test']);
    Order::factory()->create(['customer_email' => 'haystack@example.test']);

    getJson('/api/v1/admin/orders?query=needle')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

test('the order list honours the per page parameter', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    Order::factory()->count(3)->create();

    getJson('/api/v1/admin/orders?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3);
});

test('an invalid sort direction is rejected', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    getJson('/api/v1/admin/orders?direction=sideways')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('direction');
});

test('a staff member without the orders view permission cannot list orders', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    getJson('/api/v1/admin/orders')->assertForbidden();
});

test('a customer token cannot list orders', function (): void {
    actingAsApiCustomer();

    getJson('/api/v1/admin/orders')->assertForbidden();
});

test('an order is returned with its items, addresses and timeline', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->unfulfilled()->create(['notes' => 'Leave at the door']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2]);
    OrderAddress::factory()->billing()->create(['order_id' => $order->id]);
    OrderAddress::factory()->shipping()->create(['order_id' => $order->id]);
    $activity = OrderActivity::factory()->forOrder($order)->create();

    getJson('/api/v1/admin/orders/' . $order->id)
        ->assertOk()
        ->assertJsonPath('id', $order->id)
        ->assertJsonPath('notes', 'Leave at the door')
        ->assertJsonPath('is_cancellable', true)
        ->assertJsonPath('items.0.id', $item->id)
        ->assertJsonPath('items.0.quantity', 2)
        ->assertJsonPath('activities.0.id', $activity->id)
        ->assertJsonStructure([
            'billing_address' => ['first_name', 'country_code'],
            'shipping_address' => ['first_name', 'country_code'],
            'tax_details', 'shipments', 'refunds',
            'subtotal', 'total', 'paid_total', 'balance_due_total',
        ]);
});

test('an order response never exposes merchant cost fields', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $payload = getJson('/api/v1/admin/orders/' . $order->id)->assertOk()->json();

    expect($payload)->not->toHaveKey('cost_per_item')
        ->and($payload['items'][0])->not->toHaveKey('cost_per_item');
});

test('a missing order returns not found', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    getJson('/api/v1/admin/orders/999999')->assertNotFound();
});
