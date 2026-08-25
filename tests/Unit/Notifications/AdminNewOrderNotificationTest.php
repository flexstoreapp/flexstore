<?php

declare(strict_types=1);

use App\Models\Order;
use App\Notifications\AdminNewOrderNotification;
use App\Utilities\MoneyFormatter;
use Illuminate\Notifications\Messages\MailMessage;

covers(AdminNewOrderNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $order = Order::factory()->create();
    $notification = new AdminNewOrderNotification($order);

    expect($notification->via($order))->toBe(['mail']);
});

test('mail contains order id in subject', function () {
    $order = Order::factory()->create();
    $notification = new AdminNewOrderNotification($order);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id);
});

test('mail contains customer email', function () {
    $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);
    $notification = new AdminNewOrderNotification($order);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain('buyer@example.com');
});

test('mail contains formatted order total', function () {
    $order = Order::factory()->create(['total' => '150.0000', 'currency_code' => 'USD']);
    $notification = new AdminNewOrderNotification($order);

    $rendered = $notification->toMail($order)->render();
    $formatted = MoneyFormatter::format('150.0000', 'USD');

    expect($rendered)->toContain($formatted);
});

test('mail contains link to admin order page', function () {
    $order = Order::factory()->create();
    $notification = new AdminNewOrderNotification($order);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain(route('admin.orders.show', $order));
});
