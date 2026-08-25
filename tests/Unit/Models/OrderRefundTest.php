<?php

declare(strict_types=1);

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(OrderRefund::class);

uses()->group('models', 'refund');

test('has factory', function () {
    expect(OrderRefund::factory())->toBeInstanceOf(Factory::class);
});

test('touches order relationship', function () {
    $refund = new OrderRefund();

    expect($refund->getTouchedRelations())->toBe(['order']);
});

test('casts attributes correctly', function () {
    $refund = OrderRefund::factory()->create([
        'amount' => '50.0000',
        'status' => RefundStatus::Pending,
    ]);

    $casts = $refund->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('amount', 'decimal:4')
        ->toHaveKey('status', RefundStatus::class);
});

test('casts status to RefundStatus enum', function () {
    $refund = OrderRefund::factory()->create([
        'status' => RefundStatus::Pending,
    ]);

    expect($refund->status)
        ->toBeInstanceOf(RefundStatus::class)
        ->toBe(RefundStatus::Pending);
});

test('belongs to an order', function () {
    $order = Order::factory()->create();
    $refund = OrderRefund::factory()->create(['order_id' => $order->id]);

    expect($refund->order)
        ->toBeInstanceOf(Order::class);

    expect($refund->order->id)->toBe($order->id);
});

test('has many refund items', function () {
    $refund = OrderRefund::factory()->create();
    $refundItems = OrderRefundItem::factory(3)->create(['order_refund_id' => $refund->id]);

    expect($refund->items)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(OrderRefundItem::class);

    expect($refund->items->pluck('id')->all())
        ->toBe($refundItems->pluck('id')->all());
});
