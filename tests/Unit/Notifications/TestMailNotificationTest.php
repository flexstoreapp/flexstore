<?php

declare(strict_types=1);

use App\Notifications\TestMailNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(TestMailNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $notification = new TestMailNotification();

    expect($notification->via((object) []))->toBe(['mail']);
});

test('mail subject identifies it as a test', function () {
    $notification = new TestMailNotification();

    $mail = $notification->toMail((object) []);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe(__('SMTP test email'));
});

test('mail body contains the confirmation copy', function () {
    $notification = new TestMailNotification();

    $rendered = $notification->toMail((object) [])->render();

    expect($rendered)->toContain(__('If you received this message, your mail server settings are working correctly.'));
});
