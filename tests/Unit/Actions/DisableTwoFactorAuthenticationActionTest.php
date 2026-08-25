<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\User;

use App\Actions\DisableTwoFactorAuthenticationAction;
use App\Exceptions\TwoFactorNotEnabledException;
use App\Models\User;

covers(DisableTwoFactorAuthenticationAction::class, TwoFactorNotEnabledException::class);

uses()->group('actions', 'two-factor');

test('disables 2FA for user with enabled 2FA', function () {
    // User with enabled 2FA
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    $action = new DisableTwoFactorAuthenticationAction();
    $result = $action->handle($user);

    expect($result)->toBe($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('throws exception when 2FA is not enabled', function () {
    // User without 2FA enabled
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $action = new DisableTwoFactorAuthenticationAction();

    expect(fn () => $action->handle($user))
        ->toThrow(TwoFactorNotEnabledException::class);
});

test('throws exception when 2FA is pending confirmation', function () {
    // User with pending 2FA confirmation
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => null,
    ]);

    $action = new DisableTwoFactorAuthenticationAction();

    expect(fn () => $action->handle($user))
        ->toThrow(TwoFactorNotEnabledException::class);
});

test('clears secret field', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1'],
    ]);

    $action = new DisableTwoFactorAuthenticationAction();
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
});

test('clears recovery codes field', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    $action = new DisableTwoFactorAuthenticationAction();
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('clears confirmation timestamp', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1'],
    ]);

    $action = new DisableTwoFactorAuthenticationAction();
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_confirmed_at)->toBeNull();
});

test('returns updated user instance', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1'],
    ]);

    $action = new DisableTwoFactorAuthenticationAction();
    $result = $action->handle($user);

    expect($result)
        ->toBeInstanceOf(User::class)
        ->toBe($user);
});

test('handles user with only secret and confirmation', function () {
    // User with minimal 2FA setup (no recovery codes)
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => null,
    ]);

    $action = new DisableTwoFactorAuthenticationAction();
    $result = $action->handle($user);

    expect($result)->toBe($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('user no longer has enabled 2FA after disabling', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1'],
    ]);

    expect($user->hasTwoFactorEnabled())->toBeTrue();

    $action = new DisableTwoFactorAuthenticationAction();
    $action->handle($user);

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeFalse();
});

test('user no longer has pending confirmation after disabling', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1'],
    ]);

    $action = new DisableTwoFactorAuthenticationAction();
    $action->handle($user);

    $user->refresh();
    expect($user->hasPendingTwoFactorConfirmation())->toBeFalse();
});
