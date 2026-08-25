<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Rules\ValidRefundTotal;
use Illuminate\Support\Facades\Validator;

covers(ValidRefundTotal::class);

uses()->group('rules', 'refund');

function makeValidator(Order $order, array $data): Illuminate\Validation\Validator
{
    return Validator::make($data, [
        'total' => [new ValidRefundTotal($order)],
    ]);
}

test('passes when calculated total is within max refundable', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $validator = makeValidator($order, [
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 2],
        ],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('fails when calculated total exceeds max refundable', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '80.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $validator = makeValidator($order, [
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 3],
        ],
        'total' => '0',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('total'))->toBeTrue();
});

test('fails when total is zero', function () {
    $order = Order::factory()->fulfilled()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    $validator = makeValidator($order, ['total' => '0']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('total'))->toBeTrue();
});

test('includes shipping in total calculation', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '90.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '1.0000',
        'total_price' => '5.0000',
        'tax_amount' => '0.0000',
    ]);

    // Items: 1.00 * 1 = 1.00 + Shipping: 5.00 = 6.00 (within 10.00 max)
    $validator = makeValidator($order, [
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 1],
        ],
        'shipping_amount' => '5.0000',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('uses submitted total when is_manual_total is true', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '90.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '1.0000',
        'total_price' => '5.0000',
        'tax_amount' => '0.0000',
    ]);

    // Items calculate to 1.00 but submitted total overrides to 10.00 (exactly max)
    $validator = makeValidator($order, [
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 1],
        ],
        'total' => '10.0000',
        'is_manual_total' => true,
    ]);

    expect($validator->passes())->toBeTrue();
});

test('fails when submitted total exceeds max with override', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '90.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '1.0000',
        'total_price' => '5.0000',
        'tax_amount' => '0.0000',
    ]);

    $validator = makeValidator($order, [
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 1],
        ],
        'total' => '15.0000',
        'is_manual_total' => true,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('total'))->toBeTrue();
});

test('ignores submitted total when is_manual_total is false', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    // Total submitted is 90 but override is false, so calculated = 10*2 = 20
    $validator = makeValidator($order, [
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 2],
        ],
        'total' => '90.0000',
    ]);

    // Should pass because calculated total is 20.00, not 90.00
    expect($validator->passes())->toBeTrue();
});

test('accounts for tax and discount in calculation', function () {
    $order = Order::factory()->fulfilled()->create([
        'subtotal' => '100.0000',
        'tax_total' => '10.0000',
        'discount_total' => '20.0000',
        'total' => '90.0000',
        'refund_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    // Products: 25*2=50, Tax: 10/4*2=5, Discount: 20*(100/100)/4*2=10
    // Total: 50 - 10 + 5 = 45 (within 90.00 max)
    $validator = makeValidator($order, [
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 2],
        ],
    ]);

    expect($validator->passes())->toBeTrue();
});
