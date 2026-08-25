<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(ResetPasswordNotification::class);

uses()->group('notifications', 'auth');

test('uses mail channel', function () {
    $user = User::factory()->create();
    $notification = new ResetPasswordNotification('token-abc');

    expect($notification->via($user))->toBe(['mail']);
});

test('mail subject mentions resetting the password', function () {
    $user = User::factory()->create();
    $notification = new ResetPasswordNotification('token-abc');

    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain('Reset')
        ->and($mail->subject)->toContain('password');
});

test('mail body contains recipient email and reset URL', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $notification = new ResetPasswordNotification('the-token');

    $rendered = $notification->toMail($user)->render();

    expect($rendered)->toContain('reset@example.com')
        ->and($rendered)->toContain('the-token');
});

test('reset URL points to the storefront route by default', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);
    $notification = new ResetPasswordNotification('token-cust');

    $rendered = $notification->toMail($user)->render();

    expect($rendered)->toContain(route('account.password.reset', [
        'token' => 'token-cust',
        'email' => 'admin@example.com',
    ], false));
});

test('reset URL points to the admin route when the admin flag is set', function () {
    $user = User::factory()->customer()->create(['email' => 'cust@example.com']);
    $notification = new ResetPasswordNotification('token-admin', admin: true);

    $rendered = $notification->toMail($user)->render();

    expect($rendered)->toContain(route('admin.password.reset', [
        'token' => 'token-admin',
        'email' => 'cust@example.com',
    ], false));
});

test('token is publicly accessible for testing assertions', function () {
    $notification = new ResetPasswordNotification('public-token');

    expect($notification->token)->toBe('public-token');
});
