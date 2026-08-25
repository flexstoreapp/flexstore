<?php

declare(strict_types=1);

use App\Actions\ReconcileFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefundItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\User;

covers(ReconcileFulfillmentStatusAction::class);

uses()->group('actions', 'fulfillment');

function createShipment(Order $order, array $items): OrderShipment
{
    $shipment = OrderShipment::query()->create([
        'order_id' => $order->id,
        'user_id' => User::factory()->create()->id,
        'shipped_at' => now(),
    ]);

    foreach ($items as $item) {
        OrderShipmentItem::query()->create([
            'order_shipment_id' => $shipment->id,
            'order_item_id' => $item['order_item_id'],
            'quantity' => $item['quantity'],
        ]);
    }

    return $shipment;
}

test('transitions to fulfilled when all items are shipped', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Unfulfilled]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);

    createShipment($order, [['order_item_id' => $item->id, 'quantity' => 2]]);

    app(ReconcileFulfillmentStatusAction::class)->handle($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('transitions to in progress when some items are shipped', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Unfulfilled]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    createShipment($order, [['order_item_id' => $item->id, 'quantity' => 1]]);

    app(ReconcileFulfillmentStatusAction::class)->handle($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});

test('transitions to fulfilled when shipped items cover remaining after refund', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);

    $itemA = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
    ]);

    $itemB = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
    ]);

    createShipment($order, [['order_item_id' => $itemA->id, 'quantity' => 1]]);

    $refund = App\Models\OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $itemB->id,
        'quantity' => 1,
    ]);

    app(ReconcileFulfillmentStatusAction::class)->handle($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('does not transition when no shipments exist', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Unfulfilled]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
    ]);

    app(ReconcileFulfillmentStatusAction::class)->handle($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('skips canceled orders', function () {
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'canceled_at' => now(),
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
    ]);

    createShipment($order, [['order_item_id' => $item->id, 'quantity' => 1]]);

    app(ReconcileFulfillmentStatusAction::class)->handle($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('does not transition when all items are refunded without shipments', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Unfulfilled]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
    ]);

    $refund = App\Models\OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $item->id,
        'quantity' => 1,
    ]);

    app(ReconcileFulfillmentStatusAction::class)->handle($order);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});
