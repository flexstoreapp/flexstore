<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\OrderShipmentController;
use App\Http\Resources\Api\V1\Admin\OrderShipmentResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(
    OrderShipmentController::class,
    OrderShipmentResource::class,
);

uses()->group('api', 'admin-api');

function actingAsFulfilmentApiAdmin(): void
{
    actingAsApiAdmin(userWithPermissions([Permission::OrdersFulfill]));
}

test('a shipment is created for an order', function (): void {
    actingAsFulfilmentApiAdmin();

    $order = Order::factory()->unfulfilled()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'requires_shipping' => true,
        'quantity' => 3,
    ]);

    postJson("/api/v1/admin/orders/{$order->id}/shipments", [
        'tracking_number' => 'TRACK123',
        'tracking_url' => 'https://example.com/track',
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 2],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('tracking_number', 'TRACK123')
        ->assertJsonPath('order_id', $order->id)
        ->assertJsonPath('items.0.quantity', 2);

    assertDatabaseHas('order_shipments', [
        'order_id' => $order->id,
        'tracking_number' => 'TRACK123',
    ]);
    assertDatabaseHas('order_shipment_items', [
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);
});

test('creating a shipment requires shippable items', function (): void {
    actingAsFulfilmentApiAdmin();

    $order = Order::factory()->unfulfilled()->create();

    postJson("/api/v1/admin/orders/{$order->id}/shipments", ['items' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');
});

test('tracking details are updated on a shipment', function (): void {
    actingAsFulfilmentApiAdmin();

    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'OLD',
    ]);

    patchJson("/api/v1/admin/orders/{$order->id}/shipments/{$shipment->id}", [
        'tracking_number' => 'NEW123',
    ])
        ->assertOk()
        ->assertJsonPath('tracking_number', 'NEW123');

    assertDatabaseHas('order_shipments', [
        'id' => $shipment->id,
        'tracking_number' => 'NEW123',
    ]);
});

test('a shipment is deleted', function (): void {
    actingAsFulfilmentApiAdmin();

    $order = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'requires_shipping' => true]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 1,
    ]);

    deleteJson("/api/v1/admin/orders/{$order->id}/shipments/{$shipment->id}")
        ->assertNoContent();

    assertDatabaseMissing('order_shipments', ['id' => $shipment->id]);
});

test('a shipment belonging to another order is not reachable', function (): void {
    actingAsFulfilmentApiAdmin();

    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => Order::factory()->create()->id]);

    patchJson("/api/v1/admin/orders/{$order->id}/shipments/{$shipment->id}", ['tracking_number' => 'NEW'])
        ->assertNotFound();
});

test('a staff member without the fulfilment permission cannot create shipments', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->unfulfilled()->create();

    postJson("/api/v1/admin/orders/{$order->id}/shipments", ['items' => []])->assertForbidden();
});

test('a customer token cannot reach the shipment endpoints', function (): void {
    actingAsApiCustomer();

    $order = Order::factory()->create();

    postJson("/api/v1/admin/orders/{$order->id}/shipments", ['items' => []])->assertForbidden();
});
