<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Queries\CustomerOrderListQuery;

covers(CustomerOrderListQuery::class);

uses()->group('queries', 'account');

test('previews at most two items and exposes the total item count', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);
    OrderItem::factory()->count(5)->forOrder($order)->create();

    $result = app(CustomerOrderListQuery::class)->execute($user);
    $loaded = $result->items()[0];

    expect($loaded->items)->toHaveCount(2)
        ->and($loaded->items_count)->toBe(5);
});

test('returns only the customer own orders', function () {
    $user = User::factory()->create();
    Order::factory()->count(2)->create(['customer_id' => $user->id]);
    Order::factory()->create(['customer_id' => User::factory()->create()->id]);

    $result = app(CustomerOrderListQuery::class)->execute($user);

    expect($result->total())->toBe(2);
});

test('filters by fulfillment status', function () {
    $user = User::factory()->create();
    Order::factory()->fulfilled()->create(['customer_id' => $user->id]);
    Order::factory()->unfulfilled()->create(['customer_id' => $user->id]);

    $result = app(CustomerOrderListQuery::class)->execute($user, 'fulfilled');

    expect($result->total())->toBe(1);
});

test('filters cancelled orders regardless of fulfillment status', function () {
    $user = User::factory()->create();
    Order::factory()->canceled()->create(['customer_id' => $user->id]);
    Order::factory()->fulfilled()->create(['customer_id' => $user->id]);

    $result = app(CustomerOrderListQuery::class)->execute($user, 'cancelled');

    expect($result->total())->toBe(1);
});
