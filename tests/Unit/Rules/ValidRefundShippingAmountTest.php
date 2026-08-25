<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderRefund;
use App\Rules\ValidRefundShippingAmount;

covers(ValidRefundShippingAmount::class);

uses()->group('rules', 'refund');

test('passes when shipping amount is within refundable amount', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '15.0000']);

    $rule = new ValidRefundShippingAmount($order);
    $failed = false;

    $rule->validate('shipping_amount', '10.0000', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('passes when shipping amount equals refundable amount', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '15.0000']);

    $rule = new ValidRefundShippingAmount($order);
    $failed = false;

    $rule->validate('shipping_amount', '15.0000', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('fails when shipping amount exceeds refundable amount', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '10.0000']);

    $rule = new ValidRefundShippingAmount($order);
    $failed = false;

    $rule->validate('shipping_amount', '15.0000', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('considers previously refunded shipping', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '15.0000']);

    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);
    $refund->items()->create([
        'type' => 'shipping',
        'order_item_id' => null,
        'quantity' => null,
        'amount' => '10.0000',
        'restock' => false,
    ]);

    $rule = new ValidRefundShippingAmount($order);
    $failed = false;

    // Only 5.00 remaining
    $rule->validate('shipping_amount', '7.0000', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('fails for negative amounts', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '10.0000']);

    $rule = new ValidRefundShippingAmount($order);
    $failed = false;

    $rule->validate('shipping_amount', '-5.00', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('fails for non-numeric values', function () {
    $order = Order::factory()->fulfilled()->create(['shipping_total' => '10.0000']);

    $rule = new ValidRefundShippingAmount($order);
    $failed = false;

    $rule->validate('shipping_amount', 'invalid', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});
