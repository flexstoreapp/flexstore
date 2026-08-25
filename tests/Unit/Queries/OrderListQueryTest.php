<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Queries\OrderListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(OrderListQuery::class);

uses()->group('queries', 'order');

test('returns paginated orders', function () {
    Order::factory()->count(20)->create();

    $query = app(OrderListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no orders exist', function () {
    $query = app(OrderListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('includes items quantity sum', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->count(2)->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $query = app(OrderListQuery::class);
    $result = $query->execute();

    expect((int) $result->first()->items_sum_quantity)->toBe(6);
});

test('loads billing address relationship', function () {
    $order = Order::factory()->create();
    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => 'billing',
    ]);

    $query = app(OrderListQuery::class);
    $result = $query->execute();

    expect($result->first()->relationLoaded('billingAddress'))->toBeTrue();
});

test('respects per page parameter', function () {
    Order::factory()->count(10)->create();

    $query = app(OrderListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});
