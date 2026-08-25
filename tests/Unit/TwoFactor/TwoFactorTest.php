<?php

declare(strict_types=1);

namespace Tests\Unit\TwoFactor;

use App\Models\User;
use App\TwoFactor\Totp;
use App\TwoFactor\TwoFactor;

covers(TwoFactor::class);

uses()->group('unit', 'two-factor');

test('returns empty string when user has no secret for QR code', function () {
    $user = User::factory()->create(['two_factor_secret' => null]);

    $twoFactor = app(TwoFactor::class);
    $qrCode = $twoFactor->getQrCodeSvg($user);

    expect($qrCode)->toBe('');
});

test('returns QR code SVG when user has secret', function () {
    $totp = app(Totp::class);
    $twoFactor = new TwoFactor($totp);

    $secret = $totp->generateSecret();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'two_factor_secret' => $secret,
    ]);

    $qrCode = $twoFactor->getQrCodeSvg($user);

    expect($qrCode)
        ->toBeString()
        ->not->toBe('')
        ->toContain('<svg')
        ->toContain('</svg>')
        ->toContain('xmlns="http://www.w3.org/2000/svg"');
});

test('returns empty string when user has no secret for manual entry key', function () {
    $user = User::factory()->create(['two_factor_secret' => null]);

    $twoFactor = app(TwoFactor::class);
    $key = $twoFactor->getManualEntryKey($user);

    expect($key)->toBe('');
});

test('handles short secrets for manual entry key', function () {
    $user = User::factory()->create(['two_factor_secret' => 'ABC']);

    $twoFactor = app(TwoFactor::class);
    $key = $twoFactor->getManualEntryKey($user);

    expect($key)->toBe('ABC');
});

test('handles empty secret for manual entry key', function () {
    $user = User::factory()->create(['two_factor_secret' => '']);

    $twoFactor = app(TwoFactor::class);
    $key = $twoFactor->getManualEntryKey($user);

    expect($key)->toBe('');
});

test('formats manual entry key with spaces every 4 characters', function () {
    $user = User::factory()->create(['two_factor_secret' => 'ABCDEFGHIJKLMNOP']);

    $twoFactor = app(TwoFactor::class);
    $key = $twoFactor->getManualEntryKey($user);

    expect($key)->toBe('ABCD EFGH IJKL MNOP');
});

test('handles secrets not divisible by 4 for manual entry key', function () {
    $user = User::factory()->create(['two_factor_secret' => 'ABCDEFGHIJK']);

    $twoFactor = app(TwoFactor::class);
    $key = $twoFactor->getManualEntryKey($user);

    expect($key)->toBe('ABCD EFGH IJK');
});
