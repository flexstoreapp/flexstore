<?php

declare(strict_types=1);

use App\Actions\SendTestMailAction;
use App\Notifications\TestMailNotification;
use Illuminate\Support\Facades\Notification;

covers(SendTestMailAction::class);

uses()->group('actions', 'notification');

beforeEach(function () {
    Notification::fake();
});

test('dispatches test mail notification to the recipient', function () {
    (new SendTestMailAction())->handle('admin@store.com');

    Notification::assertSentOnDemand(TestMailNotification::class, function ($notification, $channels, $notifiable) {
        return in_array('mail', $channels, true)
            && $notifiable->routes['mail'] === 'admin@store.com';
    });
});
