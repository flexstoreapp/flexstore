<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Queries\RefundableOrderDataQuery;

covers(RefundableOrderDataQuery::class);

uses()->group('queries', 'refund');

test('returns full quantities when no previous refunds exist', function () {
    $order = Order::factory()->fulfilled()->create();
    $item1 = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 5]);
    $item2 = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 3]);

    $order->load('items');

    $query = app(RefundableOrderDataQuery::class);
    $result = $query->execute($order)['refundable_quantities'];

    expect($result)
        ->toHaveKey($item1->id, 5)
        ->toHaveKey($item2->id, 3);
});

test('subtracts completed refund quantities', function () {
    $order = Order::factory()->fulfilled()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 5]);

    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    $refund->items()->create([
        'type' => 'product',
        'order_item_id' => $item->id,
        'quantity' => 2,
        'amount' => '20.0000',
        'restock' => false,
    ]);

    $order->load('items');

    $query = app(RefundableOrderDataQuery::class);
    $result = $query->execute($order)['refundable_quantities'];

    expect($result)->toHaveKey($item->id, 3);
});

test('subtracts pending refund quantities to prevent over-refunding', function () {
    $order = Order::factory()->fulfilled()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 5]);

    $pendingRefund = OrderRefund::factory()->pending()->create(['order_id' => $order->id]);
    $pendingRefund->items()->create([
        'type' => 'product',
        'order_item_id' => $item->id,
        'quantity' => 2,
        'amount' => '20.0000',
        'restock' => false,
    ]);

    $order->load('items');

    $query = app(RefundableOrderDataQuery::class);
    $result = $query->execute($order)['refundable_quantities'];

    expect($result)->toHaveKey($item->id, 3);
});

test('ignores failed refund quantities', function () {
    $order = Order::factory()->fulfilled()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 5]);

    $failedRefund = OrderRefund::factory()->failed()->create(['order_id' => $order->id]);
    $failedRefund->items()->create([
        'type' => 'product',
        'order_item_id' => $item->id,
        'quantity' => 2,
        'amount' => '20.0000',
        'restock' => false,
    ]);

    $order->load('items');

    $query = app(RefundableOrderDataQuery::class);
    $result = $query->execute($order)['refundable_quantities'];

    expect($result)->toHaveKey($item->id, 5);
});

test('returns full shipping amount when no previous shipping refunds', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '15.0000']);

    $query = app(RefundableOrderDataQuery::class);

    expect($query->execute($order)['refundable_shipping_amount'])->toBe('15.0000');
});

test('subtracts previous shipping refunds from shipping total', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '15.0000']);

    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    $refund->items()->create([
        'type' => 'shipping',
        'order_item_id' => null,
        'quantity' => null,
        'amount' => '10.0000',
        'restock' => false,
    ]);

    $query = app(RefundableOrderDataQuery::class);

    expect($query->execute($order)['refundable_shipping_amount'])->toBe('5.0000');
});

test('returns zero when shipping is fully refunded', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '10.0000']);

    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    $refund->items()->create([
        'type' => 'shipping',
        'order_item_id' => null,
        'quantity' => null,
        'amount' => '10.0000',
        'restock' => false,
    ]);

    $query = app(RefundableOrderDataQuery::class);

    expect($query->execute($order)['refundable_shipping_amount'])->toBe('0.0000');
});

test('subtracts pending shipping refunds from shipping total', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '15.0000']);

    $refund = OrderRefund::factory()->pending()->create(['order_id' => $order->id]);
    $refund->items()->create([
        'type' => 'shipping',
        'order_item_id' => null,
        'quantity' => null,
        'amount' => '10.0000',
        'restock' => false,
    ]);

    $query = app(RefundableOrderDataQuery::class);

    expect($query->execute($order)['refundable_shipping_amount'])->toBe('5.0000');
});

test('returns zero when shipping was over-refunded', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '10.0000']);

    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    $refund->items()->create([
        'type' => 'shipping',
        'order_item_id' => null,
        'quantity' => null,
        'amount' => '15.0000',
        'restock' => false,
    ]);

    $query = app(RefundableOrderDataQuery::class);

    expect($query->execute($order)['refundable_shipping_amount'])->toBe('0.0000');
});

test('execute returns both quantities and shipping amount', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '10.0000']);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 4]);

    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    $refund->items()->create([
        'type' => 'product',
        'order_item_id' => $item->id,
        'quantity' => 1,
        'amount' => '5.0000',
        'restock' => false,
    ]);
    $refund->items()->create([
        'type' => 'shipping',
        'order_item_id' => null,
        'quantity' => null,
        'amount' => '3.0000',
        'restock' => false,
    ]);

    $order->load('items');

    $query = app(RefundableOrderDataQuery::class);
    $result = $query->execute($order);

    expect($result)
        ->toHaveKey('refundable_quantities')
        ->toHaveKey('refundable_shipping_amount')
        ->toHaveKey('max_refundable_amount');

    expect($result['refundable_quantities'][$item->id])->toBe(3);
    expect($result['refundable_shipping_amount'])->toBe('7.0000');
});
