<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\StorefrontSectionType;
use App\Models\StorefrontSection;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(StorefrontSection::class);

uses()->group('models', 'storefront');

test('has factory', function () {
    expect(StorefrontSection::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::FeaturedProducts,
        'sort_order' => '42',
        'is_active' => '1',
    ]);

    $casts = $section->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('type', StorefrontSectionType::class)
        ->toHaveKey('settings', 'array')
        ->toHaveKey('sort_order', 'integer')
        ->toHaveKey('is_active', 'boolean');
});

test('handles null settings gracefully', function () {
    $section = StorefrontSection::factory()->create(['settings' => null]);

    expect($section->settings)->toBeNull();
});

test('handles empty settings array', function () {
    $section = StorefrontSection::factory()->create(['settings' => []]);

    expect($section->settings)->toBeArray()
        ->and($section->settings)->toBeEmpty();
});
