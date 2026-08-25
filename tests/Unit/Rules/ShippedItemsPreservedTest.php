<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Rules\ShippedItemsPreserved;
use Illuminate\Support\Facades\Validator;

covers(ShippedItemsPreserved::class);

uses()->group('rules', 'order');

function shipItem(OrderItem $item, int $quantity): void
{
    $shipment = OrderShipment::factory()->create(['order_id' => $item->order_id]);

    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => $quantity,
    ]);
}

function validateItems(Order $order, array $items): Illuminate\Contracts\Validation\Validator
{
    return Validator::make(
        ['items' => $items],
        ['items' => [new ShippedItemsPreserved($order)]],
    );
}

test('passes when nothing has shipped', function (): void {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->forOrder($order)->create(['quantity' => 3]);

    $validator = validateItems($order, [['id' => $item->id, 'quantity' => 1]]);

    expect($validator->passes())->toBeTrue();
});

test('fails when a shipped item is removed', function (): void {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->forOrder($order)->create(['quantity' => 3]);
    shipItem($item, 2);

    $validator = validateItems($order, []);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('items'))->toBeTrue();
});

test('fails when quantity is reduced below the shipped quantity', function (): void {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->forOrder($order)->create(['quantity' => 5]);
    shipItem($item, 3);

    $validator = validateItems($order, [['id' => $item->id, 'quantity' => 2]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('items'))->toBeTrue();
});

test('passes when quantity meets or exceeds the shipped quantity', function (): void {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->forOrder($order)->create(['quantity' => 3]);
    shipItem($item, 3);

    $validator = validateItems($order, [['id' => $item->id, 'quantity' => 5]]);

    expect($validator->passes())->toBeTrue();
});

test('allows removing an unshipped item while a shipped item is preserved', function (): void {
    $order = Order::factory()->create();
    $shippedItem = OrderItem::factory()->forOrder($order)->create(['quantity' => 2]);
    OrderItem::factory()->forOrder($order)->create(['quantity' => 4]);
    shipItem($shippedItem, 2);

    $validator = validateItems($order, [['id' => $shippedItem->id, 'quantity' => 2]]);

    expect($validator->passes())->toBeTrue();
});
