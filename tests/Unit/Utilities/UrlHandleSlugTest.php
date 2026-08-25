<?php

declare(strict_types=1);

use App\Utilities\UrlHandleSlug;

covers(UrlHandleSlug::class);

uses()->group('utilities');

test('slugifies a latin value', function () {
    expect(UrlHandleSlug::make('Blue Denim Jacket', 'product'))->toBe('blue-denim-jacket');
});

test('transliterates a non-latin value', function (string $value, string $expected) {
    expect(UrlHandleSlug::make($value, 'category'))->toBe($expected);
})->with([
    ['日本語', 'ri-ben-yu'],
    ['대한민국', 'daehanmingug'],
    ['إلكترونيات', 'lktrwnyt'],
]);

test('falls back to a prefixed hash when the value has nothing to slugify', function (string $value) {
    expect(UrlHandleSlug::make($value, 'brand'))->toMatch('/^brand-[a-f0-9]{8}$/');
})->with(['★★★', '🎁', '---', '!!!', ' ']);

test('always produces a value the slug rule accepts', function (string $value) {
    expect(UrlHandleSlug::make($value, 'product'))->toMatch('/^[a-z0-9]+(?:-[a-z0-9]+)*$/');
})->with(['Blue Denim Jacket', '日本語', '★★★', '  spaced  ', 'under_score', 'T-Shirt (Red/Blue)', '-leading-and-trailing-']);

test('is deterministic for the same value', function () {
    expect(UrlHandleSlug::make('★★★', 'category'))
        ->toBe(UrlHandleSlug::make('★★★', 'category'));
});

test('produces distinct handles for different unslugifiable values', function () {
    expect(UrlHandleSlug::make('★★★', 'category'))
        ->not->toBe(UrlHandleSlug::make('🎁', 'category'));
});
