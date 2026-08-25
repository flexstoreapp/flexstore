<?php

declare(strict_types=1);

namespace Tests\Unit\TwoFactor;

use App\Models\Setting;
use App\Models\User;
use App\TwoFactor\Totp;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

covers(Totp::class);

uses()->group('unit', 'two-factor');

test('generates unique secret keys', function () {
    $totp = app(Totp::class);

    $secret1 = $totp->generateSecret();
    $secret2 = $totp->generateSecret();

    expect($secret1)
        ->toBeString()
        ->toHaveLength(16)
        ->not->toBe($secret2);

    expect($secret2)
        ->toBeString()
        ->toHaveLength(16);
});

test('generates valid base32 secret', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();

    expect($secret)->toMatch('/^[A-Z2-7]+$/');
});

test('generates QR code SVG', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $svg = $totp->generateQrCodeSvg($user, $secret);

    expect($svg)
        ->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>')
        ->toContain('xmlns="http://www.w3.org/2000/svg"');
});

test('generates QR code URL with correct format', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $url = $totp->generateQrCodeUrl($user, $secret);

    expect($url)
        ->toBeString()
        ->toStartWith('otpauth://totp/')
        ->toContain(urlencode($user->email)) // Email is URL encoded in QR code
        ->toContain($secret);
});

test('generates QR code URL with store name from settings', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    Setting::updateOrCreate(
        ['key' => 'store_name'],
        ['value' => 'Test Store', 'group' => 'store', 'type' => \App\Enums\SettingType::Text]
    );

    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $url = $totp->generateQrCodeUrl($user, $secret);

    expect($url)->toContain('Test%20Store');
});

test('generates QR code URL with fallback issuer from config', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    // Ensure no store_name setting exists
    Setting::where('key', 'store_name')->delete();

    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $url = $totp->generateQrCodeUrl($user, $secret);

    expect($url)->toContain(rawurlencode(config('google2fa.issuer')));
});

test('validates correct TOTP code', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $code = $totp->getCurrentCode($secret);

    expect($totp->validateCode($secret, $code))->toBeTrue();
});

test('rejects invalid TOTP code', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $invalidCode = '000000';

    expect($totp->validateCode($secret, $invalidCode))->toBeFalse();
});

test('validates code within time window', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $code = $totp->getCurrentCode($secret);

    expect($totp->validateCode($secret, $code))->toBeTrue();
});

test('rejects code outside time window', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();

    expect($totp->validateCode($secret, '000000'))->toBeFalse();
});

test('generates current code for secret', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $code = $totp->getCurrentCode($secret);

    expect($code)
        ->toBeString()
        ->toHaveLength(6)
        ->toMatch('/^\d{6}$/');
});

test('current code is deterministic for same time', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();
    $code1 = $totp->getCurrentCode($secret);
    $code2 = $totp->getCurrentCode($secret);

    expect($code1)->toBe($code2);
});

test('handles empty secret gracefully', function () {
    $totp = app(Totp::class);

    expect(fn () => $totp->validateCode('', '123456'))
        ->toThrow(SecretKeyTooShortException::class);
});

test('handles malformed secret gracefully', function () {
    $totp = app(Totp::class);

    expect(fn () => $totp->validateCode('invalid-secret', '123456'))
        ->toThrow(InvalidCharactersException::class);
});

test('handles non-numeric code gracefully', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();

    expect($totp->validateCode($secret, 'abcdef'))->toBeFalse();
});

test('handles code with wrong length gracefully', function () {
    $totp = app(Totp::class);

    $secret = $totp->generateSecret();

    expect($totp->validateCode($secret, '12345'))->toBeFalse();
    expect($totp->validateCode($secret, '1234567'))->toBeFalse();
});
