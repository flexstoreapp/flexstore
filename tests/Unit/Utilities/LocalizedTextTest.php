<?php

declare(strict_types=1);

use App\Utilities\LocalizedText;

covers(LocalizedText::class);

uses()->group('utilities', 'translations');

test('returns a plain string unchanged', function () {
    expect(LocalizedText::resolve('The best shop in town.'))->toBe('The best shop in town.');
});

test('returns null for empty values', function () {
    expect(LocalizedText::resolve(null))->toBeNull()
        ->and(LocalizedText::resolve(''))->toBeNull();
});

test('resolves the active locale from a translation map', function () {
    app()->setLocale('ar');

    expect(LocalizedText::resolve([
        'en' => 'The best shop in town.',
        'ar' => 'أفضل متجر في المدينة.',
    ]))->toBe('أفضل متجر في المدينة.');
});

test('falls back to the default locale when the active locale is missing', function () {
    app()->setLocale('bn');

    expect(LocalizedText::resolve('{"en":"The best shop in town.","ar":"أفضل متجر في المدينة."}'))
        ->toBe('The best shop in town.');
});

test('merges the active locale into an existing translation map', function () {
    app()->setLocale('ar');

    expect(LocalizedText::merge(
        '{"en":"The best shop in town.","ar":"قديم"}',
        'أفضل متجر في المدينة.',
    ))->toBe('{"en":"The best shop in town.","ar":"أفضل متجر في المدينة."}');
});

test('keeps a plain string when merging over a non-translated value', function () {
    expect(LocalizedText::merge('The best shop in town.', 'Updated description'))
        ->toBe('Updated description');
});
