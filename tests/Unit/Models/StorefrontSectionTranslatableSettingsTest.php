<?php

declare(strict_types=1);

use App\Enums\StorefrontSectionType;
use App\Models\StorefrontSection;

covers(StorefrontSection::class);

uses()->group('models', 'storefront');

test('wraps nested array translatable settings on create', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::InfoStrip,
        'settings' => [
            'items' => [
                ['icon_name' => 'shipping', 'title' => 'Free Shipping', 'subtitle' => 'On all orders'],
            ],
        ],
    ]);

    expect($section->settings['items'][0]['title'])->toBe(['en' => 'Free Shipping'])
        ->and($section->settings['items'][0]['subtitle'])->toBe(['en' => 'On all orders'])
        ->and($section->settings['items'][0]['icon_name'])->toBe('shipping');
});

test('skips wrapping for section types without translatable paths', function () {
    $section = StorefrontSection::factory()->create([
        'type' => StorefrontSectionType::BrandStrip,
        'settings' => [
            'brand_ids' => [],
            'grayscale' => true,
        ],
    ]);

    expect($section->settings['grayscale'])->toBe(true)
        ->and($section->settings['brand_ids'])->toBe([]);
});
