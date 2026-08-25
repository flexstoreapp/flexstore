<?php

declare(strict_types=1);

use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Queries\OrderItemBreakdownQuery;

covers(OrderItemBreakdownQuery::class);

uses()->group('queries', 'shipment');

test('returns full quantity when no shipments exist', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    expect($result)->toBe([$item->id => ['unfulfilled' => 5, 'refunded' => 0, 'total_refunded' => 0]]);
});

test('subtracts shipped quantities', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    expect($result)->toBe([$item->id => ['unfulfilled' => 3, 'refunded' => 0, 'total_refunded' => 0]]);
});

test('includes non-shippable items', function () {
    $order = Order::factory()->create();
    $shippable = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);
    $nonShippable = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    expect($result)->toBe([
        $shippable->id => ['unfulfilled' => 3, 'refunded' => 0, 'total_refunded' => 0],
        $nonShippable->id => ['unfulfilled' => 2, 'refunded' => 0, 'total_refunded' => 0],
    ]);
});

test('accumulates quantities across multiple shipments', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
    ]);

    $shipment1 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment1->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $shipment2 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment2->id,
        'order_item_id' => $item->id,
        'quantity' => 4,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    expect($result)->toBe([$item->id => ['unfulfilled' => 3, 'refunded' => 0, 'total_refunded' => 0]]);
});

test('returns empty when all items are fully shipped', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    expect($result)->toBe([]);
});

test('includes refunded quantities for partially refunded items', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);
    $refund = OrderRefund::factory()->create(['order_id' => $order->id, 'status' => RefundStatus::Completed]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $item->id,
        'type' => RefundItemType::Product,
        'quantity' => 1,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    expect($result)->toBe([$item->id => ['unfulfilled' => 2, 'refunded' => 1, 'total_refunded' => 1]]);
});

test('handles fully refunded unshipped item', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);
    $refund = OrderRefund::factory()->create(['order_id' => $order->id, 'status' => RefundStatus::Completed]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $item->id,
        'type' => RefundItemType::Product,
        'quantity' => 3,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    expect($result)->toBe([$item->id => ['unfulfilled' => 0, 'refunded' => 3, 'total_refunded' => 3]]);
});

test('handles item shipped then refunded', function () {
    $order = Order::factory()->create();
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
    $refund = OrderRefund::factory()->create(['order_id' => $order->id, 'status' => RefundStatus::Completed]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $item->id,
        'type' => RefundItemType::Product,
        'quantity' => 2,
    ]);

    $result = app(OrderItemBreakdownQuery::class)->execute($order);

    // All shipped, unshipped refunded = 0, but total_refunded = 2 (shown as badge on fulfilled card)
    expect($result)->toBe([$item->id => ['unfulfilled' => 0, 'refunded' => 0, 'total_refunded' => 2]]);
});

test('has unfulfilled items helper', function () {
    expect(OrderItemBreakdownQuery::hasUnfulfilledItems([
        1 => ['unfulfilled' => 2, 'refunded' => 1, 'total_refunded' => 1],
    ]))->toBeTrue();

    expect(OrderItemBreakdownQuery::hasUnfulfilledItems([
        1 => ['unfulfilled' => 0, 'refunded' => 3, 'total_refunded' => 3],
    ]))->toBeFalse();

    expect(OrderItemBreakdownQuery::hasUnfulfilledItems([]))->toBeFalse();
});
