<?php

declare(strict_types=1);

use App\DTOs\FulfillmentTransitionResult;
use App\Enums\FulfillmentStatus;
use App\Models\Order;

covers(FulfillmentTransitionResult::class);

uses()->group('dtos', 'fulfillment');

test('stores order and previous status', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);

    $result = new FulfillmentTransitionResult(
        order: $order,
        from: FulfillmentStatus::Unfulfilled,
    );

    expect($result->order->id)->toBe($order->id)
        ->and($result->from)->toBe(FulfillmentStatus::Unfulfilled);
});

test('properties are readonly', function () {
    $order = Order::factory()->create();

    $result = new FulfillmentTransitionResult(
        order: $order,
        from: FulfillmentStatus::Unfulfilled,
    );

    expect(fn () => $result->from = FulfillmentStatus::InProgress)->toThrow(Error::class);
});
