<?php

declare(strict_types=1);

use App\Actions\SendAdminNotificationAction;
use App\Models\Order;
use App\Models\Setting;
use App\Notifications\AdminNewOrderNotification;
use Illuminate\Support\Facades\Notification;

covers(SendAdminNotificationAction::class);

uses()->group('actions', 'notification');

beforeEach(function () {
    Notification::fake();
});

test('dispatches admin notification to store email', function () {
    Setting::setValue('store_email', 'admin@store.com');

    $order = Order::factory()->create();

    $action = new SendAdminNotificationAction();
    $action->handle(new AdminNewOrderNotification($order));

    Notification::assertSentOnDemand(AdminNewOrderNotification::class, function ($notification, $channels, $notifiable) {
        return $notifiable->routes['mail'] === 'admin@store.com';
    });
});

test('does not dispatch when store email is not set', function () {
    Setting::setValue('store_email', null);

    $order = Order::factory()->create();

    $action = new SendAdminNotificationAction();
    $action->handle(new AdminNewOrderNotification($order));

    Notification::assertNothingSent();
});
