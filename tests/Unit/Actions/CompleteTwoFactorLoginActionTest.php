<?php

declare(strict_types=1);

use App\Actions\CompleteTwoFactorLoginAction;
use App\DTOs\TwoFactorChallengeInput;
use App\Models\User;
use App\TwoFactor\RecoveryCode;
use App\TwoFactor\Totp;
use Illuminate\Validation\ValidationException;

covers(CompleteTwoFactorLoginAction::class, TwoFactorChallengeInput::class);

uses()->group('actions', 'auth', 'two-factor');

test('validates a TOTP code and tracks last login', function () {
    $totp = app(Totp::class);
    $secret = $totp->generateSecret();

    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => app(RecoveryCode::class)->generate(),
        'last_login_at' => null,
        'last_login_ip' => null,
    ]);

    app(CompleteTwoFactorLoginAction::class)->handle(
        $user,
        TwoFactorChallengeInput::fromArray(['code' => $totp->getCurrentCode($secret)]),
        '10.0.0.5',
    );

    expect($user->fresh())
        ->last_login_at->not->toBeNull()
        ->last_login_ip->toBe('10.0.0.5');
});

test('validates and consumes a recovery code', function () {
    $recoveryCodes = app(RecoveryCode::class)->generate();
    $usedCode = $recoveryCodes[0];

    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $recoveryCodes,
    ]);

    app(CompleteTwoFactorLoginAction::class)->handle(
        $user,
        TwoFactorChallengeInput::fromArray(['recovery_code' => $usedCode]),
        '127.0.0.1',
    );

    expect($user->fresh()->two_factor_recovery_codes)
        ->not->toContain($usedCode)
        ->toHaveCount(7);
});

test('throws validation exception keyed code on invalid TOTP', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
        'last_login_at' => null,
    ]);

    expect(fn () => app(CompleteTwoFactorLoginAction::class)->handle(
        $user,
        TwoFactorChallengeInput::fromArray(['code' => '000000']),
        '127.0.0.1',
    ))->toThrow(ValidationException::class);

    expect($user->fresh()->last_login_at)->toBeNull();
});

test('throws validation exception on unknown recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1-aaaaaa1234', 'code2-bbbbbb5678'],
    ]);

    expect(fn () => app(CompleteTwoFactorLoginAction::class)->handle(
        $user,
        TwoFactorChallengeInput::fromArray(['recovery_code' => 'not-a-valid-code']),
        '127.0.0.1',
    ))->toThrow(ValidationException::class);
});
