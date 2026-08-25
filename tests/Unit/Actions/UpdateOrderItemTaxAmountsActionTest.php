<?php

declare(strict_types=1);

use App\Actions\UpdateOrderItemTaxAmountsAction;
use App\DTOs\TaxDetailInput;
use App\Enums\OrderTaxDetailItemType;
use App\Models\Order;
use App\Models\OrderItem;

covers(UpdateOrderItemTaxAmountsAction::class);

uses()->group('actions', 'order');

function makeTaxDetail(?int $orderItemId, string $taxAmount): TaxDetailInput
{
    return new TaxDetailInput(
        orderItemId: $orderItemId,
        taxRateId: null,
        itemType: OrderTaxDetailItemType::Product,
        taxName: [app()->getLocale() => 'Test Tax'],
        taxRate: '10.00',
        taxableAmount: '100.0000',
        taxAmount: $taxAmount,
        isCompound: false,
        proportion: null,
    );
}

test('updates tax amounts for order items', function () {
    $order = Order::factory()->create();
    $item1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'tax_amount' => 0,
    ]);
    $item2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'tax_amount' => 0,
    ]);

    $action = new UpdateOrderItemTaxAmountsAction();
    $action->handle($order, [
        makeTaxDetail($item1->id, '10.00'),
        makeTaxDetail($item2->id, '15.50'),
    ]);

    $item1->refresh();
    $item2->refresh();

    expect($item1->tax_amount)->toBe('10.0000')
        ->and($item2->tax_amount)->toBe('15.5000');
});

test('sums multiple tax amounts for same item', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'tax_amount' => 0,
    ]);

    $action = new UpdateOrderItemTaxAmountsAction();
    $action->handle($order, [
        makeTaxDetail($item->id, '8.25'),
        makeTaxDetail($item->id, '1.75'),
        makeTaxDetail($item->id, '5.00'),
    ]);

    $item->refresh();
    expect($item->tax_amount)->toBe('15.0000');
});

test('handles decimal precision correctly', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'tax_amount' => 0,
    ]);

    $action = new UpdateOrderItemTaxAmountsAction();
    $action->handle($order, [
        makeTaxDetail($item->id, '10.3333333'),
        makeTaxDetail($item->id, '5.6666667'),
    ]);

    $item->refresh();
    expect($item->tax_amount)->toBe('16.0000');
});

test('handles rounding correctly', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'tax_amount' => 0,
    ]);

    $action = new UpdateOrderItemTaxAmountsAction();
    $action->handle($order, [
        makeTaxDetail($item->id, '1.005'),
    ]);

    $item->refresh();
    expect($item->tax_amount)->toBe('1.0050');
});

test('ignores tax details without order_item_id', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'tax_amount' => 5.00,
    ]);

    $action = new UpdateOrderItemTaxAmountsAction();
    $action->handle($order, [
        makeTaxDetail(null, '100.00'),
        makeTaxDetail($item->id, '2.50'),
    ]);

    $item->refresh();
    expect($item->tax_amount)->toBe('2.5000');
});

test('handles empty tax details array', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'tax_amount' => 10.00,
    ]);

    $action = new UpdateOrderItemTaxAmountsAction();
    $action->handle($order, []);

    $item->refresh();
    expect($item->tax_amount)->toBe('10.0000');
});

test('updates multiple items with different tax amounts', function () {
    $order = Order::factory()->create();
    $item1 = OrderItem::factory()->create(['order_id' => $order->id, 'tax_amount' => 0]);
    $item2 = OrderItem::factory()->create(['order_id' => $order->id, 'tax_amount' => 0]);
    $item3 = OrderItem::factory()->create(['order_id' => $order->id, 'tax_amount' => 0]);

    $action = new UpdateOrderItemTaxAmountsAction();
    $action->handle($order, [
        makeTaxDetail($item1->id, '5.00'),
        makeTaxDetail($item2->id, '7.50'),
        makeTaxDetail($item2->id, '2.50'),
        makeTaxDetail($item3->id, '12.25'),
    ]);

    $item1->refresh();
    $item2->refresh();
    $item3->refresh();

    expect($item1->tax_amount)->toBe('5.0000')
        ->and($item2->tax_amount)->toBe('10.0000')
        ->and($item3->tax_amount)->toBe('12.2500');
});
