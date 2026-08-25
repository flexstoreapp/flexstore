<?php

declare(strict_types=1);

use App\DTOs\RefundCalculationResult;
use App\Models\Order;
use App\Models\OrderItem;
use App\Utilities\RefundCalculator;

covers(RefundCalculator::class, RefundCalculationResult::class);

uses()->group('utilities', 'refund');

test('calculates basic product total', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $calculator = new RefundCalculator();
    $result = $calculator->calculate($order, [$item->id => 2]);

    expect($result)
        ->productTotal->toBe('20.00')
        ->taxTotal->toBe('0.00')
        ->discountTotal->toBe('0.00');
});

test('calculates prorated tax', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '10.0000',
        'discount_total' => '0.0000',
        'total' => '110.0000',
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $calculator = new RefundCalculator();
    $result = $calculator->calculate($order, [$item->id => 2]);

    // tax: 10.00 / 4 * 2 = 5.00
    expect($result)
        ->productTotal->toBe('50.00')
        ->taxTotal->toBe('5.00')
        ->discountTotal->toBe('0.00');
});

test('calculates prorated discount', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '20.0000',
        'total' => '80.0000',
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    $calculator = new RefundCalculator();
    $result = $calculator->calculate($order, [$item->id => 2]);

    // discount: 20.00 * (100/100) / 4 * 2 = 10.00
    expect($result)
        ->productTotal->toBe('50.00')
        ->taxTotal->toBe('0.00')
        ->discountTotal->toBe('10.00');
});

test('calculates combined tax and discount', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '10.0000',
        'discount_total' => '20.0000',
        'total' => '90.0000',
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $calculator = new RefundCalculator();
    $result = $calculator->calculate($order, [$item->id => 2]);

    expect($result)
        ->productTotal->toBe('50.00')
        ->taxTotal->toBe('5.00')
        ->discountTotal->toBe('10.00');
});

test('returns zeros for empty items', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '10.0000',
        'discount_total' => '0.0000',
        'total' => '110.0000',
    ]);

    $calculator = new RefundCalculator();
    $result = $calculator->calculate($order, []);

    expect($result)
        ->productTotal->toBe('0.00')
        ->taxTotal->toBe('0.00')
        ->discountTotal->toBe('0.00');
});

test('handles zero tax and discount on order', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $calculator = new RefundCalculator();
    $result = $calculator->calculate($order, [$item->id => 1]);

    expect($result)
        ->productTotal->toBe('25.00')
        ->taxTotal->toBe('0.00')
        ->discountTotal->toBe('0.00');
});

test('calculates across multiple items', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '150.0000',
        'tax_total' => '15.0000',
        'discount_total' => '0.0000',
        'total' => '165.0000',
    ]);
    $item1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '5.0000',
    ]);
    $item2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $calculator = new RefundCalculator();
    $result = $calculator->calculate($order, [
        $item1->id => 2,
        $item2->id => 1,
    ]);

    // product: (10*2) + (25*1) = 45.00
    // tax: (5/5*2) + (10/4*1) = 2.00 + 2.50 = 4.50
    expect($result)
        ->productTotal->toBe('45.00')
        ->taxTotal->toBe('4.50')
        ->discountTotal->toBe('0.00');
});
