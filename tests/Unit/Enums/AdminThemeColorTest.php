<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AdminThemeColor;

covers(AdminThemeColor::class);

uses()->group('enums', 'theme');

test('every case has a valid 7-character hex color', function (AdminThemeColor $theme) {
    expect($theme->hex())->toMatch('/^#[0-9a-f]{6}$/');
})->with(AdminThemeColor::cases());

test('hex returns the 900-shade for representative themes', function () {
    expect(AdminThemeColor::Neutral->hex())->toBe('#171717');
    expect(AdminThemeColor::Emerald->hex())->toBe('#064e3b');
    expect(AdminThemeColor::Blue->hex())->toBe('#1e3a8a');
    expect(AdminThemeColor::Pink->hex())->toBe('#831843');
});

test('hexFor resolves a known string value to its hex', function () {
    expect(AdminThemeColor::hexFor('emerald'))->toBe('#064e3b');
    expect(AdminThemeColor::hexFor('blue'))->toBe('#1e3a8a');
});

test('hexFor falls back to neutral for unknown values', function () {
    expect(AdminThemeColor::hexFor('unknown'))->toBe(AdminThemeColor::Neutral->hex());
    expect(AdminThemeColor::hexFor(''))->toBe(AdminThemeColor::Neutral->hex());
});

test('every case has a valid 7-character dark hex color', function (AdminThemeColor $theme) {
    expect($theme->darkHex())->toMatch('/^#[0-9a-f]{6}$/');
})->with(AdminThemeColor::cases());

test('darkHex returns the 600-shade for representative themes', function () {
    expect(AdminThemeColor::Neutral->darkHex())->toBe('#525252');
    expect(AdminThemeColor::Emerald->darkHex())->toBe('#059669');
    expect(AdminThemeColor::Blue->darkHex())->toBe('#2563eb');
    expect(AdminThemeColor::Pink->darkHex())->toBe('#db2777');
});

test('darkHex is always lighter than hex for contrast on dark backgrounds', function (AdminThemeColor $theme) {
    $hexLuminance = fn (string $hex): int => hexdec(mb_substr(mb_ltrim($hex, '#'), 0, 2))
        + hexdec(mb_substr(mb_ltrim($hex, '#'), 2, 2))
        + hexdec(mb_substr(mb_ltrim($hex, '#'), 4, 2));

    expect($hexLuminance($theme->darkHex()))->toBeGreaterThan($hexLuminance($theme->hex()));
})->with(AdminThemeColor::cases());

test('darkHexFor resolves a known string value to its dark hex', function () {
    expect(AdminThemeColor::darkHexFor('emerald'))->toBe('#059669');
    expect(AdminThemeColor::darkHexFor('blue'))->toBe('#2563eb');
});

test('darkHexFor falls back to neutral for unknown values', function () {
    expect(AdminThemeColor::darkHexFor('unknown'))->toBe(AdminThemeColor::Neutral->darkHex());
    expect(AdminThemeColor::darkHexFor(''))->toBe(AdminThemeColor::Neutral->darkHex());
});
