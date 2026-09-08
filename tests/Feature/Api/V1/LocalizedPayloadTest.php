<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;

use function Pest\Laravel\postJson;

uses()->group('api');

test('order tracking returns the product title as a string', function (): void {
    $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_title' => 'Merino crew knit']);

    $response = postJson(route('api.v1.orders.track'), [
        'order_number' => $order->id,
        'email' => 'buyer@example.com',
    ])->assertOk();

    expect($response->json('groups.0.items.0.product_title'))->toBe('Merino crew knit');
});
