<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderShipment;
use App\Notifications\CustomerOrderFulfilledNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(CustomerOrderFulfilledNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    $notification = new CustomerOrderFulfilledNotification($order, $shipment);

    expect($notification->via($order))->toBe(['mail']);
});

test('mail contains order id in subject', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    $notification = new CustomerOrderFulfilledNotification($order, $shipment);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id)
        ->and($mail->subject)->toContain('fulfilled');
});

test('mail contains tracking number when present', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'TRACK-12345',
        'tracking_url' => null,
    ]);
    $notification = new CustomerOrderFulfilledNotification($order, $shipment);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain('TRACK-12345');
});

test('mail links to tracking url when present', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'TRACK-12345',
        'tracking_url' => 'https://tracking.example.com/123',
    ]);
    $notification = new CustomerOrderFulfilledNotification($order, $shipment);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain('href="https://tracking.example.com/123"')
        ->and($rendered)->toContain('TRACK-12345');
});

test('mail links to order page when no tracking url', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_url' => null,
    ]);
    $notification = new CustomerOrderFulfilledNotification($order, $shipment);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain(route('account.orders.show', $order));
});
