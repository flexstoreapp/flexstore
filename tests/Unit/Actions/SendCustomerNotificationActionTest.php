<?php

declare(strict_types=1);

use App\Actions\SendCustomerNotificationAction;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CustomerOrderConfirmedNotification;
use Illuminate\Support\Facades\Notification;

covers(SendCustomerNotificationAction::class);

uses()->group('actions', 'notification');

beforeEach(function () {
    Notification::fake();
});

test('dispatches notification to customer user', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $action = new SendCustomerNotificationAction();
    $action->handle(new CustomerOrderConfirmedNotification($order), $customer);

    Notification::assertSentTo($customer, CustomerOrderConfirmedNotification::class);
});

test('dispatches notification to email when no user', function () {
    $order = Order::factory()->create();

    $action = new SendCustomerNotificationAction();
    $action->handle(
        new CustomerOrderConfirmedNotification($order),
        customerEmail: 'guest@example.com',
    );

    Notification::assertSentOnDemand(CustomerOrderConfirmedNotification::class, function ($notification, $channels, $notifiable) {
        return $notifiable->routes['mail'] === 'guest@example.com';
    });
});

test('does not dispatch when no user and no email', function () {
    $order = Order::factory()->create();

    $action = new SendCustomerNotificationAction();
    $action->handle(new CustomerOrderConfirmedNotification($order));

    Notification::assertNothingSent();
});
