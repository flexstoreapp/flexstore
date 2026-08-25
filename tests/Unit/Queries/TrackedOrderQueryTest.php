<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Queries\TrackedOrderQuery;

covers(TrackedOrderQuery::class);

uses()->group('queries', 'orders');

test('returns order totals and identity', function (): void {
    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'total' => '100.0000',
    ]);
    OrderItem::factory()->forOrder($order)->create(['quantity' => 1, 'unit_price' => '100.0000']);

    $result = app(TrackedOrderQuery::class)->execute($order->id);

    expect($result['id'])->toBe($order->id)
        ->and($result['subtotal'])->toBe('100.0000')
        ->and($result['total'])->toBe('100.0000')
        ->and($result)->toHaveKeys(['groups', 'shipping_address', 'billing_address', 'created_at']);
});

test('unshipped items are grouped as pending', function (): void {
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->create(['quantity' => 2, 'unit_price' => '10.0000']);

    $result = app(TrackedOrderQuery::class)->execute($order->id);

    expect($result['groups'])->toHaveCount(1);
    expect($result['groups'][0]['shipped'])->toBeFalse();
    expect($result['groups'][0]['key'])->toBe('pending');
    expect($result['groups'][0]['items'][0]['quantity'])->toBe(2);
    expect($result['groups'][0]['items'][0]['total_price'])->toBe('20.0000');
});

test('resolves the address state to its display name', function (): void {
    $order = Order::factory()->create();
    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'CA',
    ]);

    $result = app(TrackedOrderQuery::class)->execute($order->id);

    expect($result['shipping_address']['state'])->toBe('California');
});

test('shipped and remaining quantities split across shipment and pending groups', function (): void {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->forOrder($order)->create(['quantity' => 3, 'unit_price' => '10.0000']);

    $shipment = OrderShipment::factory()->withTracking()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 1,
    ]);

    $result = app(TrackedOrderQuery::class)->execute($order->id);

    expect($result['groups'])->toHaveCount(2);

    $shipped = $result['groups'][0];
    expect($shipped['shipped'])->toBeTrue()
        ->and($shipped['tracking_number'])->not->toBeNull()
        ->and($shipped['items'][0]['quantity'])->toBe(1);

    $pending = $result['groups'][1];
    expect($pending['shipped'])->toBeFalse()
        ->and($pending['items'][0]['quantity'])->toBe(2);
});
