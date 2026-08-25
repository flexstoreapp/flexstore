<?php

declare(strict_types=1);

use App\Actions\ResetUserPasswordAction;
use App\DTOs\PasswordResetInput;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

covers(ResetUserPasswordAction::class, PasswordResetInput::class);

uses()->group('actions', 'auth');

test('resets the password with a valid token and fires PasswordReset event', function () {
    Event::fake(PasswordReset::class);

    $user = User::factory()->create(['password' => 'old-password']);
    $originalRememberToken = $user->remember_token;
    $token = Password::createToken($user);

    $status = app(ResetUserPasswordAction::class)->handle(PasswordResetInput::fromArray([
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
        'token' => $token,
    ]));

    expect($status)->toBe(Password::PasswordReset);

    $user->refresh();
    expect(Hash::check('new-password123', $user->password))->toBeTrue();
    expect($user->remember_token)->not->toBe($originalRememberToken);

    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event) => $event->user->is($user));
});

test('returns INVALID_TOKEN when token is bad', function () {
    Event::fake(PasswordReset::class);

    $user = User::factory()->create(['password' => 'old-password']);

    $status = app(ResetUserPasswordAction::class)->handle(PasswordResetInput::fromArray([
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
        'token' => 'invalid-token',
    ]));

    expect($status)->toBe(Password::InvalidToken);
    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
    Event::assertNotDispatched(PasswordReset::class);
});

test('returns INVALID_USER for an unknown email', function () {
    $status = app(ResetUserPasswordAction::class)->handle(PasswordResetInput::fromArray([
        'email' => 'nobody@example.com',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
        'token' => 'any-token',
    ]));

    expect($status)->toBe(Password::InvalidUser);
});
