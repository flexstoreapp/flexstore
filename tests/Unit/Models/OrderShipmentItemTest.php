<?php

declare(strict_types=1);

use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(OrderShipmentItem::class);

uses()->group('models', 'shipment');

test('has factory', function () {
    expect(OrderShipmentItem::factory())->toBeInstanceOf(Factory::class);
});

test('touches orderShipment relationship', function () {
    $item = new OrderShipmentItem();

    expect($item->getTouchedRelations())->toBe(['orderShipment']);
});

test('belongs to an order shipment', function () {
    $shipment = OrderShipment::factory()->create();
    $item = OrderShipmentItem::factory()->create(['order_shipment_id' => $shipment->id]);

    expect($item->orderShipment)
        ->toBeInstanceOf(OrderShipment::class)
        ->and($item->orderShipment->id)->toBe($shipment->id);
});

test('belongs to an order item', function () {
    $orderItem = OrderItem::factory()->create();
    $item = OrderShipmentItem::factory()->create(['order_item_id' => $orderItem->id]);

    expect($item->orderItem)
        ->toBeInstanceOf(OrderItem::class)
        ->and($item->orderItem->id)->toBe($orderItem->id);
});

test('can be created with valid attributes', function () {
    $shipment = OrderShipment::factory()->create();
    $orderItem = OrderItem::factory()->create();

    $item = OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 3,
    ]);

    expect($item->order_shipment_id)->toBe($shipment->id)
        ->and($item->order_item_id)->toBe($orderItem->id)
        ->and($item->quantity)->toBe(3);
});

test('can create multiple items for same shipment', function () {
    $shipment = OrderShipment::factory()->create();
    $orderItem1 = OrderItem::factory()->create();
    $orderItem2 = OrderItem::factory()->create();

    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $orderItem1->id,
        'quantity' => 1,
    ]);

    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $orderItem2->id,
        'quantity' => 2,
    ]);

    expect($shipment->items)->toHaveCount(2);
});
