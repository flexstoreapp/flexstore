<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Models\User;
use App\Notifications\CustomerDigitalDownloadsReadyNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(CustomerDigitalDownloadsReadyNotification::class);

uses()->group('notifications', 'download');

test('is delivered over mail', function () {
    $order = Order::factory()->paid()->create();

    expect((new CustomerDigitalDownloadsReadyNotification($order))->via($order))->toBe(['mail']);
});

test('subjects the mail with the order id', function () {
    $order = Order::factory()->paid()->create();

    $mail = (new CustomerDigitalDownloadsReadyNotification($order))->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe(__('Your downloads for order #:id are ready', ['id' => $order->id]));
});

test('renders a signed link for every download on the order', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    $download = OrderItemDownload::factory()->create([
        'order_id' => $order->id,
        'name' => 'Sample Pack',
    ]);

    $rendered = (new CustomerDigitalDownloadsReadyNotification($order->fresh()))->toMail($customer)->render();

    expect($rendered)->toContain('Sample Pack')
        ->and($rendered)->toContain(route('account.orders.show', $order))
        ->and($rendered)->toContain($download->token);
});

test('omits the account link for a guest order', function () {
    $order = Order::factory()->paid()->create(['customer_id' => null]);
    OrderItemDownload::factory()->create(['order_id' => $order->id]);

    $rendered = (new CustomerDigitalDownloadsReadyNotification($order->fresh()))->toMail($order)->render();

    expect($rendered)->not->toContain(route('account.orders.show', $order));
});
