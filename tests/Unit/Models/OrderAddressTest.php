<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OrderAddressType;
use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(OrderAddress::class);

uses()->group('models', 'order');

test('has factory', function () {
    expect(OrderAddress::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $orderAddress = new OrderAddress();
    $casts = $orderAddress->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('type', OrderAddressType::class);
});

test('belongs to order relationship', function () {
    $order = Order::factory()->create();
    $orderAddress = OrderAddress::factory()->create(['order_id' => $order->id]);

    expect($orderAddress->order)->toBeInstanceOf(Order::class)
        ->and($orderAddress->order->id)->toBe($order->id);
});

test('can create billing address', function () {
    $order = Order::factory()->create();
    $orderAddress = OrderAddress::factory()->forOrder($order)->create([
        'type' => OrderAddressType::Billing,
    ]);

    expect($orderAddress->type)->toBe(OrderAddressType::Billing)
        ->and($orderAddress->order_id)->toBe($order->id);
});

test('can create shipping address', function () {
    $order = Order::factory()->create();
    $orderAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
    ]);

    expect($orderAddress->type)->toBe(OrderAddressType::Shipping)
        ->and($orderAddress->order_id)->toBe($order->id);
});

test('resolves the state code into a state name', function () {
    $orderAddress = OrderAddress::factory()->create(['country_code' => 'US', 'state' => 'CA']);

    expect($orderAddress->state_name)->toBe('California')
        ->and($orderAddress->toArray())->toHaveKey('state_name', 'California');
});
