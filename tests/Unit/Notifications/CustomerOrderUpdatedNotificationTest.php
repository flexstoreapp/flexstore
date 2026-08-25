<?php

declare(strict_types=1);

use App\Models\Order;
use App\Notifications\CustomerOrderUpdatedNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(CustomerOrderUpdatedNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderUpdatedNotification($order);

    expect($notification->via($order))->toBe(['mail']);
});

test('mail contains order id in subject', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderUpdatedNotification($order);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id)
        ->and($mail->subject)->toContain('updated');
});

test('mail contains update message with order id', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderUpdatedNotification($order);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain((string) $order->id);
});

test('mail contains link to account order page', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderUpdatedNotification($order);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain(route('account.orders.show', $order));
});
