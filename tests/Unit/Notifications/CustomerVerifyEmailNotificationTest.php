<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(CustomerVerifyEmailNotification::class);

uses()->group('notifications', 'auth');

test('uses mail channel', function () {
    $user = User::factory()->create();
    $notification = new CustomerVerifyEmailNotification;

    expect($notification->via($user))->toBe(['mail']);
});

test('mail subject mentions verifying the email', function () {
    $user = User::factory()->create();
    $notification = new CustomerVerifyEmailNotification;

    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Verify')
        ->and($mail->subject)->toContain('email');
});

test('mail body contains the recipient email', function () {
    $user = User::factory()->create(['email' => 'verify@example.com']);
    $notification = new CustomerVerifyEmailNotification;

    $rendered = $notification->toMail($user)->render();

    expect($rendered)->toContain('verify@example.com');
});

test('mail body contains a signed verification URL for the user', function () {
    $user = User::factory()->create(['email' => 'verify@example.com']);
    $notification = new CustomerVerifyEmailNotification;

    $rendered = $notification->toMail($user)->render();
    $expectedHash = hash('sha1', 'verify@example.com');

    expect($rendered)->toContain('account/verify-email/' . $user->getKey() . '/' . $expectedHash)
        ->and($rendered)->toContain('signature=');
});
