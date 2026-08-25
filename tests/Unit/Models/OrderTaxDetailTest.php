<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OrderTaxDetailItemType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTaxDetail;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(OrderTaxDetail::class);

uses()->group('models', 'tax');

test('has factory', function () {
    expect(OrderTaxDetail::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $orderTaxDetail = new OrderTaxDetail();
    $casts = $orderTaxDetail->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('item_type', OrderTaxDetailItemType::class)
        ->toHaveKey('tax_rate', 'decimal:2')
        ->toHaveKey('taxable_amount', 'decimal:4')
        ->toHaveKey('tax_amount', 'decimal:4')
        ->toHaveKey('proportion', 'decimal:4')
        ->toHaveKey('is_compound', 'boolean');
});

test('belongs to order', function () {
    $order = Order::factory()->create();
    $orderTaxDetail = OrderTaxDetail::factory()->forOrder($order)->create();

    expect($orderTaxDetail->order->id)->toBe($order->id);
});

test('belongs to order item', function () {
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->forOrder($order)->create();
    $orderTaxDetail = OrderTaxDetail::factory()
        ->forOrder($order)
        ->forOrderItem($orderItem)
        ->create();

    expect($orderTaxDetail->orderItem->id)->toBe($orderItem->id);
});

test('can have null order item for order-level taxes', function () {
    $order = Order::factory()->create();
    $orderTaxDetail = OrderTaxDetail::factory()->forOrder($order)->create([
        'order_item_id' => null,
    ]);

    expect($orderTaxDetail->order_item_id)->toBeNull()
        ->and($orderTaxDetail->orderItem)->toBeNull();
});

test('belongs to tax rate', function () {
    $taxRate = TaxRate::factory()->create();
    $orderTaxDetail = OrderTaxDetail::factory()->forTaxRate($taxRate)->create();

    expect($orderTaxDetail->taxRate->id)->toBe($taxRate->id);
});
