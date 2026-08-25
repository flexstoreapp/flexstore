<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\StorefrontAccent;

covers(StorefrontAccent::class);

uses()->group('enums', 'storefront');

test('every case has valid accent tokens', function (StorefrontAccent $accent) {
    $tokens = $accent->tokens();

    expect($tokens)->toHaveKeys(['primary', 'hover', 'tint']);

    foreach ($tokens as $token) {
        expect($token)->toStartWith('oklch(');
    }
})->with(StorefrontAccent::cases());

test('hover is darker than primary and tint is near-white', function (StorefrontAccent $accent) {
    $lightness = fn (string $oklch): float => (float) explode(' ', mb_substr($oklch, 6))[0];

    $tokens = $accent->tokens();

    expect($lightness($tokens['hover']))->toBeLessThan($lightness($tokens['primary']))
        ->and($lightness($tokens['tint']))->toBeGreaterThan(0.9);
})->with(StorefrontAccent::cases());

test('blue matches the template default accent', function () {
    expect(StorefrontAccent::Blue->tokens())->toBe([
        'primary' => 'oklch(0.48 0.16 250)',
        'hover' => 'oklch(0.41 0.15 250)',
        'tint' => 'oklch(0.95 0.03 250)',
    ]);
});

test('fromValue falls back to blue for unknown or null values', function (?string $value) {
    expect(StorefrontAccent::fromValue($value))->toBe(StorefrontAccent::Blue);
})->with(['unknown', '', 'neutral', null]);

test('every case has a valid 7-character hex color', function (StorefrontAccent $accent) {
    expect($accent->hex())->toMatch('/^#[0-9a-f]{6}$/')
        ->and($accent->darkHex())->toMatch('/^#[0-9a-f]{6}$/');
})->with(StorefrontAccent::cases());

test('hexFor and darkHexFor resolve known values', function () {
    expect(StorefrontAccent::hexFor('green'))->toBe(StorefrontAccent::Green->hex())
        ->and(StorefrontAccent::darkHexFor('green'))->toBe(StorefrontAccent::Green->darkHex());
});
