<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Queries\OrderShippedQuantitiesQuery;

covers(OrderShippedQuantitiesQuery::class);

uses()->group('queries', 'order');

test('sums shipped quantities per order item across shipments', function (): void {
    $order = Order::factory()->create();
    $shippedItem = OrderItem::factory()->forOrder($order)->create();
    $unshippedItem = OrderItem::factory()->forOrder($order)->create();

    $firstShipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    $secondShipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $firstShipment->id,
        'order_item_id' => $shippedItem->id,
        'quantity' => 2,
    ]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $secondShipment->id,
        'order_item_id' => $shippedItem->id,
        'quantity' => 3,
    ]);

    $result = app(OrderShippedQuantitiesQuery::class)->execute($order);

    expect($result)->toBe([$shippedItem->id => 5])
        ->and($result)->not->toHaveKey($unshippedItem->id);
});

test('returns an empty array when nothing has shipped', function (): void {
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->create();

    expect(app(OrderShippedQuantitiesQuery::class)->execute($order))->toBe([]);
});

test('ignores shipments belonging to other orders', function (): void {
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->create();

    $otherOrder = Order::factory()->create();
    $otherItem = OrderItem::factory()->forOrder($otherOrder)->create();
    $otherShipment = OrderShipment::factory()->create(['order_id' => $otherOrder->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $otherShipment->id,
        'order_item_id' => $otherItem->id,
        'quantity' => 4,
    ]);

    expect(app(OrderShippedQuantitiesQuery::class)->execute($order))->toBe([]);
});
