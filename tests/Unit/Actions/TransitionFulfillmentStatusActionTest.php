<?php

declare(strict_types=1);

use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;

covers(TransitionFulfillmentStatusAction::class);

uses()->group('actions', 'fulfillment');

test('no-op when transitioning to same status', function () {
    $order = Order::factory()->inProgress()->create();

    $action = app(TransitionFulfillmentStatusAction::class);
    $result = $action->handle($order, FulfillmentStatus::InProgress);

    expect($result->order->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});

test('throws on invalid transition', function () {
    $order = Order::factory()->fulfilled()->create();

    $action = app(TransitionFulfillmentStatusAction::class);
    $action->handle($order, FulfillmentStatus::OnHold);
})->throws(InvalidStatusTransitionException::class);

test('successful transition updates status in database', function () {
    $order = Order::factory()->unfulfilled()->create();

    $action = app(TransitionFulfillmentStatusAction::class);
    $result = $action->handle($order, FulfillmentStatus::InProgress);

    expect($result->order->fulfillment_status)->toBe(FulfillmentStatus::InProgress)
        ->and($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});

test('returns from status in result', function () {
    $order = Order::factory()->unfulfilled()->create();

    $action = app(TransitionFulfillmentStatusAction::class);
    $result = $action->handle($order, FulfillmentStatus::InProgress);

    expect($result->from)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($result->order->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});

test('recalculates LTV when transitioning to Fulfilled', function () {
    $order = Order::factory()->inProgress()->create(['total' => '100.0000', 'refund_total' => '0.0000']);

    $action = app(TransitionFulfillmentStatusAction::class);
    $action->handle($order, FulfillmentStatus::Fulfilled);

    $customer = $order->customer->fresh();
    expect($customer->lifetime_value)->toBe('100.0000');
});
