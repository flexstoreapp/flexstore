<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderRefund;
use App\Notifications\CustomerOrderRefundedNotification;
use App\Utilities\MoneyFormatter;
use Illuminate\Notifications\Messages\MailMessage;

covers(CustomerOrderRefundedNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $order = Order::factory()->create();
    $refund = OrderRefund::factory()->create(['order_id' => $order->id]);
    $notification = new CustomerOrderRefundedNotification($order, $refund);

    expect($notification->via($order))->toBe(['mail']);
});

test('mail contains order id in subject', function () {
    $order = Order::factory()->create();
    $refund = OrderRefund::factory()->create(['order_id' => $order->id]);
    $notification = new CustomerOrderRefundedNotification($order, $refund);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id);
});

test('mail contains formatted refund amount', function () {
    $order = Order::factory()->create(['currency_code' => 'USD']);
    $refund = OrderRefund::factory()->create(['order_id' => $order->id, 'amount' => '25.5000']);
    $notification = new CustomerOrderRefundedNotification($order, $refund);

    $rendered = $notification->toMail($order)->render();
    $formatted = MoneyFormatter::format('25.5000', 'USD');

    expect($rendered)->toContain($formatted);
});

test('mail contains link to account order page', function () {
    $order = Order::factory()->create();
    $refund = OrderRefund::factory()->create(['order_id' => $order->id]);
    $notification = new CustomerOrderRefundedNotification($order, $refund);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain(route('account.orders.show', $order));
});
