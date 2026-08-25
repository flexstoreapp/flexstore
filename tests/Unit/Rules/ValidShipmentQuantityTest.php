<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Rules\ValidShipmentQuantity;

covers(ValidShipmentQuantity::class);

uses()->group('rules', 'shipment');

test('passes when shipment quantity is within fulfillable amount', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $passes = true;

    $rule->validate('items.0.quantity', 3, function () use (&$passes) {
        $passes = false;
    });

    expect($passes)->toBeTrue();
});

test('fails when shipment quantity exceeds fulfillable amount', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 5, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('passes when shipment quantity equals remaining fulfillable amount', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 2,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 3, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeFalse();
});

test('considers previously shipped quantities', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 3,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 3, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('fails when quantity is zero', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 0, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('fails when quantity is negative', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', -1, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('fails when unable to extract order item id from attribute', function () {
    $order = Order::factory()->create();

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['invalid' => 'structure']);
    $validationFailed = false;
    $errorMessage = '';

    $rule->validate('invalid.attribute', 1, function ($message) use (&$validationFailed, &$errorMessage) {
        $validationFailed = true;
        $errorMessage = $message;
    });

    expect($validationFailed)->toBeTrue()
        ->and($errorMessage)->toContain('Unable to determine');
});

test('handles multiple shipments for the same item', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
    ]);

    $shipment1 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment1->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 3,
    ]);

    $shipment2 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment2->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 4,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    // 10 - 3 - 4 = 3 remaining, requesting 4 should fail
    $rule->validate('items.0.quantity', 4, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('validates multiple items with a single batch query', function () {
    $order = Order::factory()->create();
    $item1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);
    $item2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item1->id,
        'quantity' => 2,
    ]);

    $rule = new ValidShipmentQuantity($order);
    $rule->setData([
        'items' => [
            ['order_item_id' => $item1->id],
            ['order_item_id' => $item2->id],
        ],
    ]);

    $failures = [];

    // item1: 5 - 2 shipped = 3 remaining, requesting 3 — should pass
    $rule->validate('items.0.quantity', 3, function () use (&$failures) {
        $failures[] = 'items.0';
    });

    // item2: 3 remaining, requesting 4 — should fail
    $rule->validate('items.1.quantity', 4, function () use (&$failures) {
        $failures[] = 'items.1';
    });

    expect($failures)->toBe(['items.1']);
});
