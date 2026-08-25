<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use App\Rules\ValidRefundQuantity;

covers(ValidRefundQuantity::class);

uses()->group('rules', 'refund');

test('passes when refund quantity is within stock', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $passes = true;

    $rule->validate('items.0.quantity', 3, function () use (&$passes) {
        $passes = false;
    });

    expect($passes)->toBeTrue();
});

test('fails when refund quantity exceeds stock', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 5, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('considers previously refunded quantities', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $previousRefund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $previousRefund->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 2,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    // Try to refund 4 more items (2 already refunded + 4 = 6 > 5 available)
    $rule->validate('items.0.quantity', 4, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('passes when refund quantity equals remaining stock', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $previousRefund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $previousRefund->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 2,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    // Try to refund exactly the remaining 3 items
    $rule->validate('items.0.quantity', 3, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeFalse();
});

test('handles multiple previous refunds for same order item', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
    ]);

    $refund1 = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund1->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 3,
    ]);

    $refund2 = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $refund2->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 2,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    // Try to refund 6 more items (3 + 2 + 6 = 11 > 10 available)
    $rule->validate('items.0.quantity', 6, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('passes when no previous refunds exist', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 5, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeFalse();
});

test('fails when trying to refund zero quantity', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 0, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('fails when trying to refund negative quantity', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', -1, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('considers pending refund quantities', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $pendingRefund = OrderRefund::factory()->pending()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $pendingRefund->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 3,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    // Only 2 remaining (5 - 3 pending), so 3 should fail
    $rule->validate('items.0.quantity', 3, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('ignores failed refund quantities', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $failedRefund = OrderRefund::factory()->failed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $failedRefund->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 3,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => $orderItem->id]]]);
    $validationFailed = false;

    // All 5 should still be available since the failed refund doesn't count
    $rule->validate('items.0.quantity', 5, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeFalse();
});

test('handles non-existent order item', function () {
    $order = Order::factory()->create();

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['items' => [['order_item_id' => 99999]]]);
    $validationFailed = false;

    $rule->validate('items.0.quantity', 1, function () use (&$validationFailed) {
        $validationFailed = true;
    });

    expect($validationFailed)->toBeTrue();
});

test('fails when unable to extract order item id from attribute', function () {
    $order = Order::factory()->create();

    $rule = new ValidRefundQuantity($order);
    $rule->setData(['invalid' => 'structure']);
    $validationFailed = false;
    $errorMessage = '';

    $rule->validate('invalid.attribute', 1, function ($message) use (&$validationFailed, &$errorMessage) {
        $validationFailed = true;
        $errorMessage = $message;
    });

    expect($validationFailed)->toBeTrue();
    expect($errorMessage)->toContain('Unable to determine');
});

test('validates multiple items with a single batch query', function () {
    $order = Order::factory()->create();
    $item1 = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 5]);
    $item2 = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 3]);

    $previousRefund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    OrderRefundItem::factory()->create([
        'order_refund_id' => $previousRefund->id,
        'order_item_id' => $item1->id,
        'quantity' => 2,
    ]);

    $rule = new ValidRefundQuantity($order);
    $rule->setData([
        'items' => [
            ['order_item_id' => $item1->id],
            ['order_item_id' => $item2->id],
        ],
    ]);

    $failures = [];

    // item1: 5 - 2 refunded = 3 remaining, requesting 3 — should pass
    $rule->validate('items.0.quantity', 3, function () use (&$failures) {
        $failures[] = 'items.0';
    });

    // item2: 3 remaining, requesting 4 — should fail
    $rule->validate('items.1.quantity', 4, function () use (&$failures) {
        $failures[] = 'items.1';
    });

    expect($failures)->toBe(['items.1']);
});
