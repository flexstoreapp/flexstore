<?php

declare(strict_types=1);

use App\Models\Order;
use App\Notifications\AdminOrderCanceledNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(AdminOrderCanceledNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $order = Order::factory()->create();
    $notification = new AdminOrderCanceledNotification($order);

    expect($notification->via($order))->toBe(['mail']);
});

test('mail contains order id in subject', function () {
    $order = Order::factory()->create();
    $notification = new AdminOrderCanceledNotification($order);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id)
        ->and($mail->subject)->toContain('canceled');
});

test('mail contains customer email', function () {
    $order = Order::factory()->create(['customer_email' => 'canceled@example.com']);
    $notification = new AdminOrderCanceledNotification($order);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain('canceled@example.com');
});

test('mail contains link to admin order page', function () {
    $order = Order::factory()->create();
    $notification = new AdminOrderCanceledNotification($order);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain(route('admin.orders.show', $order));
});
