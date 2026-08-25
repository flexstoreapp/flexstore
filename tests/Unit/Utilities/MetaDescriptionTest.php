<?php

declare(strict_types=1);

use App\Utilities\MetaDescription;

covers(MetaDescription::class);

uses()->group('utilities');

test('prefers the seo description when present', function () {
    expect(MetaDescription::resolve('Curated tech picks.', '<p>Long body copy.</p>'))
        ->toBe('Curated tech picks.');
});

test('falls back to the stripped description when there is no seo description', function () {
    expect(MetaDescription::resolve('', '<p>Shop the <strong>latest</strong> gadgets today.</p>'))
        ->toBe('Shop the latest gadgets today.');
});

test('collapses whitespace in the fallback', function () {
    expect(MetaDescription::resolve('', "<p>Line one</p>\n<p>Line   two</p>"))
        ->toBe('Line one Line two');
});

test('truncates the fallback on a word boundary', function () {
    $description = str_repeat('word ', 60);

    $result = MetaDescription::resolve('', $description, 40);

    expect(mb_strlen($result))->toBeLessThanOrEqual(41)
        ->and($result)->toEndWith('...')
        ->and($result)->not->toContain('wor.');
});

test('returns an empty string when both inputs are empty', function () {
    expect(MetaDescription::resolve('', ''))->toBe('');
});
