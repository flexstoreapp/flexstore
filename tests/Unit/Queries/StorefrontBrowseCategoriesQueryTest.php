<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Setting;
use App\Queries\StorefrontBrowseCategoriesQuery;

covers(StorefrontBrowseCategoriesQuery::class);

uses()->group('queries', 'storefront');

test('returns empty array when no browse categories are configured', function () {
    Setting::setValue('storefront_header_browse_categories', []);

    expect(app(StorefrontBrowseCategoriesQuery::class)->execute())->toBe([]);
});

test('returns each configured category with its subtree', function () {
    $electronics = Category::factory()->create(['name' => ['en' => 'Electronics'], 'is_active' => true]);
    Category::factory()->create(['name' => ['en' => 'Audio'], 'parent_id' => $electronics->id, 'is_active' => true]);
    $fashion = Category::factory()->create(['name' => ['en' => 'Fashion'], 'is_active' => true]);

    Setting::setValue('storefront_header_browse_categories', [
        ['category_id' => $electronics->id, 'is_mega_menu' => true, 'featured_image_id' => null],
        ['category_id' => $fashion->id, 'is_mega_menu' => false, 'featured_image_id' => null],
    ]);

    $result = app(StorefrontBrowseCategoriesQuery::class)->execute();

    expect($result)->toHaveCount(2)
        ->and($result[0]['label'])->toBe('Electronics')
        ->and($result[0]['is_mega_menu'])->toBeTrue()
        ->and($result[0]['url'])->toContain($electronics->url_handle)
        ->and($result[0]['children'])->toHaveCount(1)
        ->and($result[0]['children'][0]['label'])->toBe('Audio')
        ->and($result[1]['label'])->toBe('Fashion')
        ->and($result[1]['is_mega_menu'])->toBeFalse();
});

test('builds a featured tile from the featured title, link, and image', function () {
    $category = Category::factory()->create(['name' => ['en' => 'Electronics'], 'is_active' => true]);
    $media = App\Models\Media::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [
        [
            'category_id' => $category->id,
            'is_mega_menu' => true,
            'featured_image_id' => $media->id,
            'featured_title' => ['en' => 'New season audio'],
            'featured_url' => '/products?sale=1',
        ],
    ]);

    $result = app(StorefrontBrowseCategoriesQuery::class)->execute();

    expect($result[0]['featured']['title'])->toBe('New season audio')
        ->and($result[0]['featured']['url'])->toBe('/products?sale=1')
        ->and($result[0]['featured']['image'])->not->toBeNull();
});

test('featured title is empty and link is null when not configured', function () {
    $category = Category::factory()->create(['name' => ['en' => 'Electronics'], 'is_active' => true]);
    $media = App\Models\Media::factory()->create();

    Setting::setValue('storefront_header_browse_categories', [
        ['category_id' => $category->id, 'is_mega_menu' => true, 'featured_image_id' => $media->id],
    ]);

    $result = app(StorefrontBrowseCategoriesQuery::class)->execute();

    expect($result[0]['featured']['title'])->toBe('')
        ->and($result[0]['featured']['url'])->toBeNull()
        ->and($result[0]['featured']['image'])->not->toBeNull();
});

test('excludes inactive subcategories', function () {
    $root = Category::factory()->create(['is_active' => true]);
    Category::factory()->create(['name' => ['en' => 'Active'], 'parent_id' => $root->id, 'is_active' => true]);
    Category::factory()->create(['name' => ['en' => 'Hidden'], 'parent_id' => $root->id, 'is_active' => false]);

    Setting::setValue('storefront_header_browse_categories', [
        ['category_id' => $root->id, 'is_mega_menu' => true, 'featured_image_id' => null],
    ]);

    $result = app(StorefrontBrowseCategoriesQuery::class)->execute();

    expect($result[0]['children'])->toHaveCount(1)
        ->and($result[0]['children'][0]['label'])->toBe('Active');
});
