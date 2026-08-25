<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Media;
use App\Models\Setting;
use App\Queries\AdminHeaderBrowseCategoriesQuery;

covers(AdminHeaderBrowseCategoriesQuery::class);

uses()->group('queries', 'storefront');

test('returns empty array when no browse categories are configured', function () {
    Setting::setValue('storefront_header_browse_categories', []);

    expect(app(AdminHeaderBrowseCategoriesQuery::class)->execute())->toBe([]);
});

test('hydrates each configured category for the admin form', function () {
    $electronics = Category::factory()->create(['name' => ['en' => 'Electronics']]);
    $fashion = Category::factory()->create(['name' => ['en' => 'Fashion']]);

    Setting::setValue('storefront_header_browse_categories', [
        ['category_id' => $electronics->id, 'is_mega_menu' => true, 'featured_image_id' => null],
        ['category_id' => $fashion->id, 'is_mega_menu' => false, 'featured_url' => '/shop'],
    ]);

    $result = app(AdminHeaderBrowseCategoriesQuery::class)->execute();

    expect($result)->toHaveCount(2)
        ->and($result[0]['category']->id)->toBe($electronics->id)
        ->and($result[0]['is_mega_menu'])->toBeTrue()
        ->and($result[0]['featured_image'])->toBeNull()
        ->and($result[0]['featured_title'])->toBeNull()
        ->and($result[0]['featured_url'])->toBeNull()
        ->and($result[1]['category']->id)->toBe($fashion->id)
        ->and($result[1]['is_mega_menu'])->toBeFalse()
        ->and($result[1]['featured_url'])->toBe('/shop');
});

test('resolves featured title for the current locale', function () {
    $category = Category::factory()->create();
    $media = Media::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [
        [
            'category_id' => $category->id,
            'is_mega_menu' => true,
            'featured_image_id' => $media->id,
            'featured_title' => ['en' => 'New season audio', 'ar' => 'صوتيات الموسم'],
            'featured_url' => '/products?sale=1',
        ],
    ]);

    $result = app(AdminHeaderBrowseCategoriesQuery::class)->execute();

    expect($result[0]['featured_title'])->toBe('New season audio')
        ->and($result[0]['featured_url'])->toBe('/products?sale=1')
        ->and($result[0]['featured_image'])->toBeInstanceOf(Media::class)
        ->and($result[0]['featured_image']->id)->toBe($media->id);
});

test('falls back to the default locale then the first translation', function () {
    $category = Category::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [
        [
            'category_id' => $category->id,
            'is_mega_menu' => false,
            'featured_title' => ['en' => 'English title', 'de' => 'Deutscher Titel'],
        ],
    ]);

    app()->setLocale('ar');

    expect(app(AdminHeaderBrowseCategoriesQuery::class)->execute()[0]['featured_title'])->toBe('English title');

    Setting::setValue('storefront_header_browse_categories', [
        [
            'category_id' => $category->id,
            'is_mega_menu' => false,
            'featured_title' => ['de' => 'Deutscher Titel'],
        ],
    ]);

    expect(app(AdminHeaderBrowseCategoriesQuery::class)->execute()[0]['featured_title'])->toBe('Deutscher Titel');

    app()->setLocale('en');
});

test('drops items whose category no longer exists', function () {
    $category = Category::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [
        ['category_id' => 999999, 'is_mega_menu' => true],
        ['category_id' => $category->id, 'is_mega_menu' => false],
    ]);

    $result = app(AdminHeaderBrowseCategoriesQuery::class)->execute();

    expect($result)->toHaveCount(1)
        ->and($result[0]['category']->id)->toBe($category->id);
});

test('defaults mega menu to false when the flag is missing', function () {
    $category = Category::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [
        ['category_id' => $category->id],
    ]);

    expect(app(AdminHeaderBrowseCategoriesQuery::class)->execute()[0]['is_mega_menu'])->toBeFalse();
});
