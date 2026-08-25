<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderShipment;
use App\Notifications\CustomerTrackingUpdatedNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(CustomerTrackingUpdatedNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    $notification = new CustomerTrackingUpdatedNotification($order, $shipment);

    expect($notification->via($order))->toBe(['mail']);
});

test('mail contains order id in subject', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    $notification = new CustomerTrackingUpdatedNotification($order, $shipment);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id);
});

test('mail contains tracking number when present', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'TRACK-99999',
        'tracking_url' => null,
    ]);
    $notification = new CustomerTrackingUpdatedNotification($order, $shipment);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain('TRACK-99999');
});

test('mail links to tracking url when present', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'TRACK-77777',
        'tracking_url' => 'https://tracking.example.com/456',
    ]);
    $notification = new CustomerTrackingUpdatedNotification($order, $shipment);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain('href="https://tracking.example.com/456"')
        ->and($rendered)->toContain('TRACK-77777');
});

test('mail links to order page when no tracking url', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_url' => null,
    ]);
    $notification = new CustomerTrackingUpdatedNotification($order, $shipment);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain(route('account.orders.show', $order));
});
