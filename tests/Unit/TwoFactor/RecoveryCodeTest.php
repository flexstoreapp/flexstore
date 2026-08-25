<?php

declare(strict_types=1);

namespace Tests\Unit\TwoFactor;

use App\Models\User;
use App\TwoFactor\RecoveryCode;

covers(RecoveryCode::class);

uses()->group('unit', 'two-factor');

test('generates exactly 8 recovery codes', function () {
    $recoveryCode = new RecoveryCode();

    $codes = $recoveryCode->generate();

    expect($codes)
        ->toBeArray()
        ->toHaveCount(8);
});

test('generates unique recovery codes', function () {
    $recoveryCode = new RecoveryCode();

    $codes = $recoveryCode->generate();
    $uniqueCodes = array_unique($codes);

    expect($uniqueCodes)->toHaveCount(8);
});

test('generates codes with correct format', function () {
    $recoveryCode = new RecoveryCode();

    $codes = $recoveryCode->generate();

    foreach ($codes as $code) {
        expect($code)
            ->toBeString()
            ->toMatch('/^[A-Z0-9]+-[A-Z0-9]+$/')
            ->toHaveLength(21); // 10 + 1 + 10 characters
    }
});

test('generates uppercase codes', function () {
    $recoveryCode = new RecoveryCode();

    $codes = $recoveryCode->generate();

    foreach ($codes as $code) {
        expect($code)->toBe(mb_strtoupper($code));
    }
});

test('validates and consumes correct recovery code', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => ['test1-code1', 'test2-code2', 'test3-code3'],
    ]);

    $recoveryCode = new RecoveryCode();
    $result = $recoveryCode->validateAndConsume($user, 'test2-code2');

    expect($result)->toBeTrue();

    // Verify code was removed
    $user = $user->refresh();
    $remainingCodes = $user->two_factor_recovery_codes;
    expect($remainingCodes)
        ->toHaveCount(2)
        ->toContain('test1-code1')
        ->toContain('test3-code3')
        ->not->toContain('test2-code2');
});

test('rejects invalid recovery code', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => ['test1-code1', 'test2-code2'],
    ]);

    $recoveryCode = new RecoveryCode();
    $result = $recoveryCode->validateAndConsume($user, 'invalid-code');

    expect($result)->toBeFalse();

    // Verify no codes were removed
    $user->refresh();
    $remainingCodes = $user->two_factor_recovery_codes;
    expect($remainingCodes)->toHaveCount(2);
});

test('returns false when user has no recovery codes', function () {
    $user = User::factory()->create(['two_factor_recovery_codes' => null]);

    $recoveryCode = new RecoveryCode();
    $result = $recoveryCode->validateAndConsume($user, 'any-code');

    expect($result)->toBeFalse();
});

test('handles already used recovery code', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => ['test1-code1', 'test2-code2'],
    ]);

    $recoveryCode = new RecoveryCode();

    $result1 = $recoveryCode->validateAndConsume($user, 'test1-code1');
    expect($result1)->toBeTrue();

    // Try to use the same code again
    $result2 = $recoveryCode->validateAndConsume($user, 'test1-code1');
    expect($result2)->toBeFalse();
});

test('maintains array indices after code consumption', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => ['code1-test1', 'code2-test2', 'code3-test3'],
    ]);

    // Consume middle code
    $recoveryCode = new RecoveryCode();
    $recoveryCode->validateAndConsume($user, 'code2-test2');

    $user->refresh();
    $remainingCodes = $user->two_factor_recovery_codes;

    // Should have sequential indices (0, 1) not (0, 2)
    expect(array_keys($remainingCodes))->toBe([0, 1]);
    expect(array_values($remainingCodes))->toBe(['code1-test1', 'code3-test3']);
});

test('handles consuming all recovery codes', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => ['code1-test1', 'code2-test2'],
    ]);

    // Consume all codes
    $recoveryCode = new RecoveryCode();
    $recoveryCode->validateAndConsume($user, 'code1-test1');
    $recoveryCode->validateAndConsume($user, 'code2-test2');

    $user->refresh();
    $remainingCodes = $user->two_factor_recovery_codes;

    expect($remainingCodes)->toBeEmpty();
});
