<?php

declare(strict_types=1);

use App\Actions\DeleteOrderShipmentAction;
use App\Enums\FulfillmentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Admin\OrderShipmentController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\User;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(OrderShipmentController::class, DeleteOrderShipmentAction::class);

uses()->group('shipment');

function actingAsWithFulfillPerm(): User
{
    $role = Role::query()->firstOrCreate(['name' => 'test-fulfill-delete']);
    $perm = SpatiePermission::query()->firstOrCreate(['name' => Permission::OrdersFulfill]);
    $role->syncPermissions([$perm]);

    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}

test('shipment can be deleted', function () {
    actingAsWithFulfillPerm();

    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    delete(route('admin.orders.shipments.destroy', [$order, $shipment]))
        ->assertRedirect();

    assertDatabaseMissing('order_shipments', ['id' => $shipment->id]);
    assertDatabaseMissing('order_shipment_items', ['order_shipment_id' => $shipment->id]);
});

test('deleting shipment reverts fulfillment status to unfulfilled', function () {
    actingAsWithFulfillPerm();

    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    delete(route('admin.orders.shipments.destroy', [$order, $shipment]))
        ->assertRedirect();

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('delete returns 409 for canceled order', function () {
    actingAsWithFulfillPerm();

    $order = Order::factory()->create(['canceled_at' => now()]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    delete(route('admin.orders.shipments.destroy', [$order, $shipment]))
        ->assertStatus(409);
});

test('delete rejects shipment belonging to another order', function () {
    actingAsWithFulfillPerm();

    $order = Order::factory()->create();
    $otherOrder = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $otherOrder->id]);

    delete(route('admin.orders.shipments.destroy', [$order, $shipment]))
        ->assertNotFound();
});

test('admin without permission gets 403 on delete', function () {
    $role = Role::query()->firstOrCreate(['name' => 'no-perms-delete']);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    delete(route('admin.orders.shipments.destroy', [$order, $shipment]))
        ->assertForbidden();
});
