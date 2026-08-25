<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(OrderShipment::class);

uses()->group('models', 'shipment');

test('has factory', function () {
    expect(OrderShipment::factory())->toBeInstanceOf(Factory::class);
});

test('touches order relationship', function () {
    $shipment = new OrderShipment();

    expect($shipment->getTouchedRelations())->toBe(['order']);
});

test('casts attributes correctly', function () {
    $casts = (new OrderShipment())->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('shipped_at', 'datetime');
});

test('belongs to an order', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    expect($shipment->order)
        ->toBeInstanceOf(Order::class)
        ->and($shipment->order->id)->toBe($order->id);
});

test('belongs to a user', function () {
    $user = User::factory()->create();
    $shipment = OrderShipment::factory()->create(['user_id' => $user->id]);

    expect($shipment->user)
        ->toBeInstanceOf(User::class)
        ->and($shipment->user->id)->toBe($user->id);
});

test('user is nullable', function () {
    $shipment = OrderShipment::factory()->create(['user_id' => null]);

    expect($shipment->user)->toBeNull();
});

test('has many items', function () {
    $shipment = OrderShipment::factory()->create();
    $items = OrderShipmentItem::factory(3)->create(['order_shipment_id' => $shipment->id]);

    expect($shipment->items)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(OrderShipmentItem::class)
        ->and($shipment->items->pluck('id')->all())->toBe($items->pluck('id')->all());
});

test('shipped_at is set by default', function () {
    $shipment = OrderShipment::factory()->create();

    expect($shipment->shipped_at)->not->toBeNull();
});
