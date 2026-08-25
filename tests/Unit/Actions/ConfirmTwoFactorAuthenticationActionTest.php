<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\User;

use App\Actions\ConfirmTwoFactorAuthenticationAction;
use App\Exceptions\TwoFactorConfirmationNotPendingException;
use App\Exceptions\TwoFactorSetupNotStartedException;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

covers([
    ConfirmTwoFactorAuthenticationAction::class,
    TwoFactorConfirmationNotPendingException::class,
    TwoFactorSetupNotStartedException::class,
]);

uses()->group('actions', 'two-factor');

test('confirms 2FA with valid code', function () {
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($secret);

    // User with pending 2FA confirmation
    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => null,
    ]);

    $action = app(ConfirmTwoFactorAuthenticationAction::class);
    $action->handle($user, $code);

    $user->refresh();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
    expect($user->two_factor_recovery_codes)->toHaveCount(8);
    expect($user->two_factor_recovery_codes)->each->toMatch('/^[A-Z0-9]{10}-[A-Z0-9]{10}$/');
});

test('throws exception when user has no pending confirmation', function () {
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($secret);

    // User without pending confirmation
    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $action = app(ConfirmTwoFactorAuthenticationAction::class);

    expect(fn () => $action->handle($user, $code))
        ->toThrow(TwoFactorConfirmationNotPendingException::class);
});

test('throws exception when user has no secret', function () {
    // User without secret
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $action = app(ConfirmTwoFactorAuthenticationAction::class);

    expect(fn () => $action->handle($user, '123456'))
        ->toThrow(TwoFactorSetupNotStartedException::class);
});

test('throws exception with invalid code', function () {
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $invalidCode = '000000';

    // User with pending 2FA confirmation
    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => null,
    ]);

    $action = app(ConfirmTwoFactorAuthenticationAction::class);

    expect(fn () => $action->handle($user, $invalidCode))
        ->toThrow(ValidationException::class);
});

test('generates and stores recovery codes on confirmation', function () {
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($secret);

    // User with pending 2FA confirmation
    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => null,
    ]);

    $action = app(ConfirmTwoFactorAuthenticationAction::class);
    $action->handle($user, $code);

    $user->refresh();
    expect($user->two_factor_recovery_codes)->toHaveCount(8);
    expect($user->two_factor_recovery_codes)->each->toMatch('/^[A-Z0-9]{10}-[A-Z0-9]{10}$/');
});

test('sets confirmation timestamp', function () {
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($secret);

    // User with pending 2FA confirmation
    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => null,
    ]);

    $action = app(ConfirmTwoFactorAuthenticationAction::class);
    $action->handle($user, $code);

    $user->refresh();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
});

test('does not modify secret during confirmation', function () {
    $google2fa = new Google2FA();
    $originalSecret = $google2fa->generateSecretKey();
    $code = $google2fa->getCurrentOtp($originalSecret);

    // User with pending 2FA confirmation
    $user = User::factory()->create([
        'two_factor_secret' => $originalSecret,
        'two_factor_confirmed_at' => null,
    ]);

    $action = app(ConfirmTwoFactorAuthenticationAction::class);
    $action->handle($user, $code);

    $user->refresh();
    expect($user->two_factor_secret)->toBe($originalSecret);
});
