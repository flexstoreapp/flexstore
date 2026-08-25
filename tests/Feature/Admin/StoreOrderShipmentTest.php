<?php

declare(strict_types=1);

use App\Actions\StoreOrderShipmentAction;
use App\Actions\UpdateOrderShipmentAction;
use App\Enums\FulfillmentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Admin\OrderShipmentController;
use App\Http\Requests\Admin\StoreOrderShipmentRequest;
use App\Http\Requests\Admin\UpdateOrderShipmentRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

covers([
    OrderShipmentController::class,
    StoreOrderShipmentRequest::class,
    UpdateOrderShipmentRequest::class,
    StoreOrderShipmentAction::class,
    UpdateOrderShipmentAction::class,
]);

uses()->group('shipment');

function actingAsWithFulfillPermission(): User
{
    $role = Role::query()->firstOrCreate(['name' => 'test-fulfill']);
    $perm = SpatiePermission::query()->firstOrCreate(['name' => Permission::OrdersFulfill]);
    $role->syncPermissions([$perm]);

    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}

test('admin with permission can create a shipment', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'tracking_number' => 'TRACK123',
        'tracking_url' => 'https://example.com/track',
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect();

    assertDatabaseHas('order_shipments', [
        'order_id' => $order->id,
        'tracking_number' => 'TRACK123',
    ]);

    assertDatabaseHas('order_shipment_items', [
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);
});

test('returns validation errors for missing items', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [],
    ]);

    $response->assertSessionHasErrors('items');
});

test('rejects quantity exceeding fulfillable amount', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 5],
        ],
    ]);

    $response->assertSessionHasErrors('items.0.quantity');
});

test('admin without permission gets 403', function () {
    $role = Role::query()->firstOrCreate(['name' => 'no-perms']);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 1],
        ],
    ]);

    $response->assertForbidden();
});

test('can update tracking info on existing shipment', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'OLD',
    ]);

    $response = patch(route('admin.orders.shipments.update', [$order, $shipment]), [
        'tracking_number' => 'NEW123',
        'tracking_url' => 'https://track.example.com/NEW123',
    ]);

    $response->assertRedirect();

    assertDatabaseHas('order_shipments', [
        'id' => $shipment->id,
        'tracking_number' => 'NEW123',
        'tracking_url' => 'https://track.example.com/NEW123',
    ]);
});

test('fulfillment status transitions to fulfilled when all items shipped', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);

    post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 2],
        ],
    ]);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('admin without permission gets 403 on update', function () {
    $role = Role::query()->firstOrCreate(['name' => 'no-perms']);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    $response = patch(route('admin.orders.shipments.update', [$order, $shipment]), [
        'tracking_number' => 'NEW',
    ]);

    $response->assertForbidden();
});

test('rejects invalid tracking url format', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    $response = patch(route('admin.orders.shipments.update', [$order, $shipment]), [
        'tracking_url' => 'not-a-url',
    ]);

    $response->assertSessionHasErrors('tracking_url');
});

test('rejects invalid tracking url on store', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'tracking_url' => 'not-a-url',
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('tracking_url');
});

test('rejects item belonging to another order', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $otherOrder = Order::factory()->create();
    $otherItem = OrderItem::factory()->create([
        'order_id' => $otherOrder->id,
        'quantity' => 3,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $otherItem->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('items.0.order_item_id');
});

test('rejects duplicate order item ids', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 6,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 3],
            ['order_item_id' => $item->id, 'quantity' => 3],
        ],
    ]);

    $response->assertSessionHasErrors('items.1.order_item_id');
});

test('update rejects shipment belonging to another order', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->create();
    $otherOrder = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $otherOrder->id]);

    $response = patch(route('admin.orders.shipments.update', [$order, $shipment]), [
        'tracking_number' => 'HACK',
    ]);

    $response->assertNotFound();
});

test('store returns 409 for canceled order', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->create(['canceled_at' => now()]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(409);
});

test('update returns 409 for canceled order', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->create(['canceled_at' => now()]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    $response = patch(route('admin.orders.shipments.update', [$order, $shipment]), [
        'tracking_number' => 'NEW',
    ]);

    $response->assertStatus(409);
});

test('cannot create a shipment for a digital-only order', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'requires_shipping' => false,
        'quantity' => 1,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirectBack()->assertInvalid([
        'items' => 'This order has no items that require shipping.',
    ]);
});

test('cannot ship a digital item on a mixed order', function () {
    actingAsWithFulfillPermission();

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'requires_shipping' => true,
        'quantity' => 1,
    ]);
    $digital = OrderItem::factory()->create([
        'order_id' => $order->id,
        'requires_shipping' => false,
        'quantity' => 1,
    ]);

    $response = post(route('admin.orders.shipments.store', $order), [
        'items' => [
            ['order_item_id' => $digital->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirectBack()->assertInvalid('items.0.order_item_id');
});
