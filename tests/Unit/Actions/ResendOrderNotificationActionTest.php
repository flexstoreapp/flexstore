<?php

declare(strict_types=1);

use App\Actions\ResendOrderNotificationAction;
use App\Enums\OrderActivityType;
use App\Enums\OrderEmailType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderRefund;
use App\Models\OrderShipment;
use App\Models\User;
use App\Notifications\CustomerOrderCanceledNotification;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Notifications\CustomerOrderFulfilledNotification;
use App\Notifications\CustomerOrderRefundedNotification;
use App\Notifications\CustomerOrderUpdatedNotification;
use App\Notifications\CustomerTrackingUpdatedNotification;
use Illuminate\Support\Facades\Notification;

covers(ResendOrderNotificationAction::class);

uses()->group('actions', 'notification');

test('maps each email type to the right customer notification', function (OrderEmailType $type, string $notification) {
    Notification::fake();

    $order = Order::factory()->create();
    $user = User::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);

    app(ResendOrderNotificationAction::class)->handle($user, $order, $type, $shipment, $refund);

    Notification::assertSentTo($order->customer, $notification);
})->with([
    'confirmation' => [OrderEmailType::OrderConfirmed, CustomerOrderConfirmedNotification::class],
    'update' => [OrderEmailType::OrderUpdated, CustomerOrderUpdatedNotification::class],
    'cancellation' => [OrderEmailType::OrderCanceled, CustomerOrderCanceledNotification::class],
    'fulfillment' => [OrderEmailType::OrderFulfilled, CustomerOrderFulfilledNotification::class],
    'tracking' => [OrderEmailType::TrackingUpdated, CustomerTrackingUpdatedNotification::class],
    'refund' => [OrderEmailType::OrderRefunded, CustomerOrderRefundedNotification::class],
]);

test('records an EmailResent activity attributed to the acting user', function () {
    Notification::fake();

    $order = Order::factory()->create();
    $user = User::factory()->create();

    app(ResendOrderNotificationAction::class)->handle($user, $order, OrderEmailType::OrderConfirmed);

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::EmailResent)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->metadata['email_type'])->toBe('order_confirmed');
});

test('throws when a shipment is required but missing', function (OrderEmailType $type) {
    Notification::fake();

    $order = Order::factory()->create();
    $user = User::factory()->create();

    expect(fn () => app(ResendOrderNotificationAction::class)->handle($user, $order, $type))
        ->toThrow(InvalidArgumentException::class);

    Notification::assertNothingSent();
    expect(OrderActivity::query()->where('order_id', $order->id)->where('type', OrderActivityType::EmailResent)->exists())
        ->toBeFalse();
})->with([
    'fulfillment' => OrderEmailType::OrderFulfilled,
    'tracking' => OrderEmailType::TrackingUpdated,
]);

test('throws when a refund is required but missing', function () {
    Notification::fake();

    $order = Order::factory()->create();
    $user = User::factory()->create();

    expect(fn () => app(ResendOrderNotificationAction::class)->handle($user, $order, OrderEmailType::OrderRefunded))
        ->toThrow(InvalidArgumentException::class);

    Notification::assertNothingSent();
});
