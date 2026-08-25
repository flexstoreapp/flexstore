<?php

declare(strict_types=1);

use App\Actions\StoreOrderActivityAction;
use App\Enums\OrderActivityType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

covers(StoreOrderActivityAction::class);

uses()->group('actions', 'order');

test('creates a fulfillment status change activity', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create();

    $action = new StoreOrderActivityAction();
    $result = $action->handle(
        order: $order,
        type: OrderActivityType::FulfillmentStatusChanged,
        user: $user,
        comment: 'Order is being prepared',
        metadata: [
            'from_status' => 'unfulfilled',
            'to_status' => 'in_progress',
        ],
    );

    expect($result)->toBeInstanceOf(OrderActivity::class)
        ->and($result->order_id)->toBe($order->id)
        ->and($result->user_id)->toBe($user->id)
        ->and($result->type)->toBe(OrderActivityType::FulfillmentStatusChanged)
        ->and($result->comment)->toBe('Order is being prepared');

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => OrderActivityType::FulfillmentStatusChanged->value,
        'comment' => 'Order is being prepared',
    ]);
});

test('creates a note activity', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create();

    $action = new StoreOrderActivityAction();
    $result = $action->handle(
        order: $order,
        type: OrderActivityType::NoteAdded,
        user: $user,
        comment: 'Customer called about delivery time',
    );

    expect($result)->toBeInstanceOf(OrderActivity::class)
        ->and($result->type)->toBe(OrderActivityType::NoteAdded)
        ->and($result->comment)->toBe('Customer called about delivery time')
        ->and($result->metadata)->toBeNull();
});

test('creates activity without user for system events', function () {
    $order = Order::factory()->create();

    $action = new StoreOrderActivityAction();
    $result = $action->handle(
        order: $order,
        type: OrderActivityType::PaymentStatusChanged,
        metadata: ['from_status' => 'unpaid', 'to_status' => 'paid'],
    );

    expect($result->user_id)->toBeNull();

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'user_id' => null,
    ]);
});

test('persists activity record to database', function () {
    $order = Order::factory()->create();

    $initialCount = OrderActivity::count();

    $action = new StoreOrderActivityAction();
    $result = $action->handle(
        order: $order,
        type: OrderActivityType::OrderPlaced,
    );

    expect(OrderActivity::count())->toBe($initialCount + 1);

    $savedRecord = OrderActivity::find($result->id);
    expect($savedRecord)->not->toBeNull()
        ->and($savedRecord->order_id)->toBe($order->id);
});
