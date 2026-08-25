<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Locale;

covers(Locale::class);

uses()->group('enums', 'locale');

test('nativeName returns the native label for a known code', function () {
    expect(Locale::nativeName('en'))->toBe('English');
    expect(Locale::nativeName('ar'))->toBe('العربية');
    expect(Locale::nativeName('bn'))->toBe('বাংলা');
});

test('nativeName falls back to the code when unknown', function () {
    expect(Locale::nativeName('xx'))->toBe('xx');
});

test('isRtl returns true for RTL locales', function (string $code) {
    expect(Locale::isRtl($code))->toBeTrue();
})->with(['ar', 'fa', 'he', 'ur', 'ps', 'ku', 'sd', 'ug', 'yi']);

test('isRtl returns false for LTR locales', function (string $code) {
    expect(Locale::isRtl($code))->toBeFalse();
})->with(['en', 'bn', 'hi', 'es', 'fr', 'de', 'zh', 'ja', 'ko']);

test('isRtl normalizes region suffixes and casing', function () {
    expect(Locale::isRtl('fa_IR'))->toBeTrue();
    expect(Locale::isRtl('ar-SA'))->toBeTrue();
    expect(Locale::isRtl('AR'))->toBeTrue();
    expect(Locale::isRtl('en_US'))->toBeFalse();
    expect(Locale::isRtl('EN-GB'))->toBeFalse();
});

test('isRtl returns false for empty or unknown codes', function () {
    expect(Locale::isRtl(''))->toBeFalse();
    expect(Locale::isRtl('xx'))->toBeFalse();
});
