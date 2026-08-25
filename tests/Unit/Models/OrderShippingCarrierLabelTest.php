<?php

declare(strict_types=1);

use App\Models\Order;

covers(Order::class);

uses()->group('order', 'shipping');

test('falls back to the translated carrier name when there is no provider', function () {
    $order = Order::factory()->create([
        'shipping_carrier_name' => ['en' => 'Custom Shipping'],
    ]);

    expect($order->shipping_carrier_label)->toBe('Custom Shipping');
});
