<?php

declare(strict_types=1);

use App\Actions\UpdateOrderShipmentAction;
use App\DTOs\UpdateOrderShipmentInput;
use App\Enums\OrderActivityType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderShipment;
use App\Models\User;
use App\Notifications\CustomerTrackingUpdatedNotification;
use Illuminate\Support\Facades\Notification;

covers(UpdateOrderShipmentAction::class, UpdateOrderShipmentInput::class);

uses()->group('actions', 'shipment');

test('updates tracking number', function () {
    $order = Order::factory()->unfulfilled()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'OLD123',
    ]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);
    $result = $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_number' => 'NEW456',
    ]));

    expect($result->tracking_number)->toBe('NEW456');
});

test('updates tracking url', function () {
    $order = Order::factory()->unfulfilled()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_url' => 'https://old.example.com',
    ]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);
    $result = $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_url' => 'https://new.example.com',
    ]));

    expect($result->tracking_url)->toBe('https://new.example.com');
});

test('preserves untouched tracking fields on partial update', function () {
    $order = Order::factory()->unfulfilled()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'KEEP123',
        'tracking_url' => 'https://keep.example.com',
    ]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);

    $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_number' => 'NEW456',
    ]));

    expect($shipment->fresh()->tracking_url)->toBe('https://keep.example.com');

    $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_url' => 'https://new.example.com',
    ]));

    expect($shipment->fresh()->tracking_number)->toBe('NEW456');
});

test('does not log activity when nothing changes', function () {
    $order = Order::factory()->unfulfilled()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'SAME123',
        'tracking_url' => 'https://example.com',
    ]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);
    $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_number' => 'SAME123',
        'tracking_url' => 'https://example.com',
    ]));

    expect(OrderActivity::query()->where('order_id', $order->id)->count())->toBe(0);
});

test('logs activity on tracking update', function () {
    $order = Order::factory()->unfulfilled()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'TRACK123',
    ]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);
    $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_number' => 'TRACK456',
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::TrackingUpdated)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['shipment_id'])->toBe($shipment->id)
        ->and($activity->metadata['tracking_number'])->toBe('TRACK456');
});

test('does not send notification when tracking did not change', function () {
    Notification::fake();

    $order = Order::factory()->unfulfilled()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'SAME123',
        'tracking_url' => 'https://example.com',
    ]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);
    $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_number' => 'SAME123',
        'tracking_url' => 'https://example.com',
        'notify_customer' => true,
    ]));

    Notification::assertNothingSent();
});

test('sends notification when tracking changed and notify_customer is true', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $order = Order::factory()->unfulfilled()->create(['customer_id' => $customer->id]);
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'OLD123',
    ]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);
    $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray([
        'tracking_number' => 'NEW456',
        'notify_customer' => true,
    ]));

    Notification::assertSentTo($customer, CustomerTrackingUpdatedNotification::class);
});

test('rejects update for canceled orders', function () {
    $order = Order::factory()->create(['canceled_at' => now()]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    $user = User::factory()->create();

    $action = app(UpdateOrderShipmentAction::class);
    $action->handle($user, $shipment, UpdateOrderShipmentInput::fromArray(['tracking_number' => 'NEW']));
})->throws(App\Exceptions\OrderConflictException::class);
