<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\StoreConfigController;
use App\Http\Controllers\Api\V1\TrackOrderController;
use App\Models\Order;
use App\Queries\ApiStoreConfigQuery;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(
    StoreConfigController::class,
    ApiStoreConfigQuery::class,
    TrackOrderController::class,
);

uses()->group('api');

test('the config endpoint exposes what a client needs to boot', function (): void {
    getJson(route('api.v1.config'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'store' => ['name', 'country_code'],
                'locales' => ['default', 'available'],
                'currencies' => ['base', 'available'],
                'checkout' => ['guest_checkout_enabled', 'prices_include_tax'],
                'policies',
            ],
        ]);
});

test('a guest can track an order with the order number and email', function (): void {
    $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);

    postJson(route('api.v1.orders.track'), [
        'order_number' => $order->id,
        'email' => 'BUYER@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
});

test('tracking fails when the email does not match the order', function (): void {
    $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);

    postJson(route('api.v1.orders.track'), [
        'order_number' => $order->id,
        'email' => 'someone-else@example.com',
    ])->assertJsonValidationErrors('order_number');
});
