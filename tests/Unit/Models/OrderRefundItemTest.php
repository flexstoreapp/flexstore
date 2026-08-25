<?php

declare(strict_types=1);

use App\Enums\RefundStatus;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;

covers(OrderRefundItem::class);

uses()->group('models', 'refund');

test('touches refund relationship', function () {
    $refundItem = new OrderRefundItem();

    expect($refundItem->getTouchedRelations())->toBe(['refund']);
});

test('casts attributes correctly', function () {
    $refundItem = OrderRefundItem::factory()->create([
        'amount' => '25.9900',
    ]);

    $casts = $refundItem->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('amount', 'decimal:4');
});

test('belongs to a refund', function () {
    $refund = OrderRefund::factory()->create();
    $refundItem = OrderRefundItem::factory()->create(['order_refund_id' => $refund->id]);

    expect($refundItem->refund)
        ->toBeInstanceOf(OrderRefund::class);

    expect($refundItem->refund->id)->toBe($refund->id);
});

test('belongs to an order item', function () {
    $orderItem = OrderItem::factory()->create();
    $refundItem = OrderRefundItem::factory()->create(['order_item_id' => $orderItem->id]);

    expect($refundItem->orderItem)
        ->toBeInstanceOf(OrderItem::class);

    expect($refundItem->orderItem->id)->toBe($orderItem->id);
});

test('can be created with valid attributes', function () {
    $refund = OrderRefund::factory()->create();
    $orderItem = OrderItem::factory()->create();

    $refundItem = OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 2,
        'amount' => '50.0000',
    ]);

    expect($refundItem->order_refund_id)->toBe($refund->id);
    expect($refundItem->order_item_id)->toBe($orderItem->id);
    expect($refundItem->quantity)->toBe(2);
    expect($refundItem->amount)->toBe('50.0000');
});

test('can create multiple refund items for same refund', function () {
    $refund = OrderRefund::factory()->create();
    $orderItem1 = OrderItem::factory()->create();
    $orderItem2 = OrderItem::factory()->create();

    $refundItem1 = OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $orderItem1->id,
        'quantity' => 1,
        'amount' => '25.0000',
    ]);

    $refundItem2 = OrderRefundItem::factory()->create([
        'order_refund_id' => $refund->id,
        'order_item_id' => $orderItem2->id,
        'quantity' => 2,
        'amount' => '50.0000',
    ]);

    expect($refund->items)->toHaveCount(2);
    expect($refund->items->pluck('id')->all())
        ->toContain($refundItem1->id, $refundItem2->id);
});

test('can create multiple refund items for same order item across different refunds', function () {
    $orderItem = OrderItem::factory()->create();
    $refund1 = OrderRefund::factory()->create();
    $refund2 = OrderRefund::factory()->create();

    $refundItem1 = OrderRefundItem::factory()->create([
        'order_refund_id' => $refund1->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 1,
        'amount' => '25.0000',
    ]);

    $refundItem2 = OrderRefundItem::factory()->create([
        'order_refund_id' => $refund2->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 1,
        'amount' => '25.0000',
    ]);

    expect($refundItem1->order_item_id)->toBe($orderItem->id);
    expect($refundItem2->order_item_id)->toBe($orderItem->id);
    expect($refundItem1->order_refund_id)->not->toBe($refundItem2->order_refund_id);
});

test('completedTotalsByOrderItem sums only completed refund amounts and quantities', function () {
    $orderItem1 = OrderItem::factory()->create();
    $orderItem2 = OrderItem::factory()->create();

    $completedRefund = OrderRefund::factory()->create(['status' => RefundStatus::Completed]);
    $pendingRefund = OrderRefund::factory()->create(['status' => RefundStatus::Pending]);

    OrderRefundItem::factory()->create([
        'order_refund_id' => $completedRefund->id,
        'order_item_id' => $orderItem1->id,
        'quantity' => 3,
        'amount' => '30.0000',
    ]);

    OrderRefundItem::factory()->create([
        'order_refund_id' => $completedRefund->id,
        'order_item_id' => $orderItem1->id,
        'quantity' => 2,
        'amount' => '20.0000',
    ]);

    OrderRefundItem::factory()->create([
        'order_refund_id' => $pendingRefund->id,
        'order_item_id' => $orderItem1->id,
        'quantity' => 5,
        'amount' => '10.0000',
    ]);

    OrderRefundItem::factory()->create([
        'order_refund_id' => $completedRefund->id,
        'order_item_id' => $orderItem2->id,
        'quantity' => 1,
        'amount' => '15.0000',
    ]);

    $results = OrderRefundItem::completedTotalsByOrderItem()->get()->keyBy('order_item_id');

    expect($results)->toHaveCount(2)
        ->and((float) $results[$orderItem1->id]->refund_amount)->toBe(50.0)
        ->and((int) $results[$orderItem1->id]->refund_quantity)->toBe(5)
        ->and((float) $results[$orderItem2->id]->refund_amount)->toBe(15.0)
        ->and((int) $results[$orderItem2->id]->refund_quantity)->toBe(1);
});
