<?php

declare(strict_types=1);

use App\Actions\SendPasswordResetLinkAction;
use App\DTOs\PasswordResetLinkInput;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

covers(SendPasswordResetLinkAction::class, PasswordResetLinkInput::class);

uses()->group('actions', 'auth');

test('sends a reset link notification to the user', function () {
    Notification::fake();

    $user = User::factory()->create();

    $status = app(SendPasswordResetLinkAction::class)->handle(PasswordResetLinkInput::fromArray(['email' => $user->email]));

    expect($status)->toBe(Password::ResetLinkSent);
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('returns INVALID_USER status for an unknown email', function () {
    Notification::fake();

    $status = app(SendPasswordResetLinkAction::class)->handle(PasswordResetLinkInput::fromArray(['email' => 'nobody@example.com']));

    expect($status)->toBe(Password::InvalidUser);
    Notification::assertNothingSent();
});
