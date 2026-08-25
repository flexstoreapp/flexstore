<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\User;

use App\Actions\RegenerateRecoveryCodesAction;
use App\Exceptions\TwoFactorNotEnabledException;
use App\Models\User;

covers(RegenerateRecoveryCodesAction::class, TwoFactorNotEnabledException::class);

uses()->group('actions', 'two-factor');

test('regenerates recovery codes for user with enabled 2FA', function () {
    // User with enabled 2FA
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['old1-code1', 'old2-code2'],
    ]);

    $action = app(RegenerateRecoveryCodesAction::class);
    $result = $action->handle($user);

    expect($result)->toBe($user);

    $user->refresh();
    expect($user->two_factor_recovery_codes)->toHaveCount(8);
    expect($user->two_factor_recovery_codes)->each->toMatch('/^[A-Z0-9]{10}-[A-Z0-9]{10}$/');
});

test('throws exception when 2FA is not enabled', function () {
    // User without 2FA enabled
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    $action = app(RegenerateRecoveryCodesAction::class);

    expect(fn () => $action->handle($user))
        ->toThrow(TwoFactorNotEnabledException::class);
});

test('throws exception when 2FA has pending confirmation', function () {
    // User with pending 2FA confirmation
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    $action = app(RegenerateRecoveryCodesAction::class);

    expect(fn () => $action->handle($user))
        ->toThrow(TwoFactorNotEnabledException::class);
});

test('replaces existing recovery codes', function () {
    $oldCodes = ['old1-code1', 'old2-code2'];

    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $oldCodes,
    ]);

    $action = app(RegenerateRecoveryCodesAction::class);
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_recovery_codes)->toHaveCount(8);
    expect($user->two_factor_recovery_codes)->not->toBe($oldCodes);
    expect($user->two_factor_recovery_codes)->each->toMatch('/^[A-Z0-9]{10}-[A-Z0-9]{10}$/');
});

test('does not modify secret or confirmation timestamp', function () {
    $originalSecret = 'original-secret';
    $originalConfirmation = now()->subHour();

    $user = User::factory()->create([
        'two_factor_secret' => $originalSecret,
        'two_factor_confirmed_at' => $originalConfirmation,
        'two_factor_recovery_codes' => ['old-codes'],
    ]);

    $action = app(RegenerateRecoveryCodesAction::class);
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBe($originalSecret);
    expect($user->two_factor_confirmed_at->timestamp)->toBe($originalConfirmation->timestamp);
});

test('returns updated user instance', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['old-codes'],
    ]);

    $action = app(RegenerateRecoveryCodesAction::class);
    $result = $action->handle($user);

    expect($result)
        ->toBeInstanceOf(User::class)
        ->toBe($user);
});

test('generates new codes through recovery code class', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => null,
    ]);

    $action = app(RegenerateRecoveryCodesAction::class);
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_recovery_codes)->toHaveCount(8);
    expect($user->two_factor_recovery_codes)->each->toMatch('/^[A-Z0-9]{10}-[A-Z0-9]{10}$/');
});
