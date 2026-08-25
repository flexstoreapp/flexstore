<?php

declare(strict_types=1);

use App\Actions\UpdateStorefrontHeaderAction;
use App\DTOs\UpdateSettingsInput;
use App\Models\Category;
use App\Models\Setting;

covers(UpdateStorefrontHeaderAction::class);

uses()->group('actions', 'storefront');

test('updates sticky without touching browse categories', function () {
    $category = Category::factory()->create();
    $existing = [[
        'category_id' => $category->id,
        'is_mega_menu' => true,
        'featured_image_id' => null,
        'featured_title' => ['en' => 'Keep me'],
        'featured_url' => null,
    ]];

    Setting::setValue('storefront_header_browse_categories', $existing);
    Setting::setValue('storefront_header_sticky', false);

    app(UpdateStorefrontHeaderAction::class)->handle(UpdateSettingsInput::fromArray([
        'storefront_header_sticky' => true,
    ]));

    expect(Setting::getValue('storefront_header_sticky'))->toBeTrue()
        ->and(Setting::getValue('storefront_header_browse_categories'))->toBe($existing);
});

test('stores new browse categories with the current locale title map', function () {
    $category = Category::factory()->create();

    app(UpdateStorefrontHeaderAction::class)->handle(UpdateSettingsInput::fromArray([
        'storefront_header_browse_categories' => [[
            'category_id' => $category->id,
            'is_mega_menu' => true,
            'featured_image_id' => null,
            'featured_title' => 'New arrivals',
            'featured_url' => '/products',
        ]],
    ]));

    expect(Setting::getValue('storefront_header_browse_categories'))->toBe([[
        'category_id' => $category->id,
        'is_mega_menu' => true,
        'featured_image_id' => null,
        'featured_title' => ['en' => 'New arrivals'],
        'featured_url' => '/products',
    ]]);
});

test('merges featured title per locale preserving other locales', function () {
    $category = Category::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [[
        'category_id' => $category->id,
        'is_mega_menu' => true,
        'featured_image_id' => null,
        'featured_title' => ['en' => 'English title', 'ar' => 'العنوان'],
        'featured_url' => null,
    ]]);

    app(UpdateStorefrontHeaderAction::class)->handle(UpdateSettingsInput::fromArray([
        'storefront_header_browse_categories' => [[
            'category_id' => $category->id,
            'is_mega_menu' => true,
            'featured_title' => 'Updated English',
        ]],
    ]));

    expect(Setting::getValue('storefront_header_browse_categories')[0]['featured_title'])
        ->toBe(['en' => 'Updated English', 'ar' => 'العنوان']);
});

test('clears the current locale title without dropping other locales', function () {
    $category = Category::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [[
        'category_id' => $category->id,
        'is_mega_menu' => false,
        'featured_image_id' => null,
        'featured_title' => ['en' => 'English title', 'ar' => 'العنوان'],
        'featured_url' => null,
    ]]);

    app(UpdateStorefrontHeaderAction::class)->handle(UpdateSettingsInput::fromArray([
        'storefront_header_browse_categories' => [[
            'category_id' => $category->id,
            'is_mega_menu' => false,
            'featured_title' => '',
        ]],
    ]));

    expect(Setting::getValue('storefront_header_browse_categories')[0]['featured_title'])
        ->toBe(['ar' => 'العنوان']);
});

test('allows clearing browse categories with an empty array', function () {
    $category = Category::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [[
        'category_id' => $category->id,
        'is_mega_menu' => false,
    ]]);

    app(UpdateStorefrontHeaderAction::class)->handle(UpdateSettingsInput::fromArray([
        'storefront_header_browse_categories' => [],
    ]));

    expect(Setting::getValue('storefront_header_browse_categories'))->toBe([]);
});
