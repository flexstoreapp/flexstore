<?php

declare(strict_types=1);

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Models\Category;
use App\Models\Media;
use App\Models\MenuItem;
use App\Queries\StorefrontHeaderMenuQuery;

covers(StorefrontHeaderMenuQuery::class);

uses()->group('queries', 'storefront');

test('returns active header menu items ordered by sort order', function () {
    MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'Second'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/second',
        'sort_order' => 2,
        'is_active' => true,
    ]);
    MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'First'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/first',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'Hidden'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/hidden',
        'sort_order' => 3,
        'is_active' => false,
    ]);

    $result = app(StorefrontHeaderMenuQuery::class)->execute();

    expect($result)->toHaveCount(2)
        ->and($result[0]['label'])->toBe('First')
        ->and($result[0]['url'])->toBe('/first')
        ->and($result[1]['label'])->toBe('Second');
});

test('resolves category link url and nests children', function () {
    $category = Category::factory()->create();

    $parent = MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'Shop'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/shop',
        'is_mega_menu' => true,
        'is_active' => true,
    ]);

    MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'Category'],
        'link_type' => MenuItemLinkType::Category,
        'category_id' => $category->id,
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $result = app(StorefrontHeaderMenuQuery::class)->execute();

    expect($result)->toHaveCount(1)
        ->and($result[0]['label'])->toBe('Shop')
        ->and($result[0]['is_mega_menu'])->toBeTrue()
        ->and($result[0]['children'])->toHaveCount(1)
        ->and($result[0]['children'][0]['label'])->toBe('Category')
        ->and($result[0]['children'][0]['url'])->toContain($category->url_handle);
});

test('includes featured payload for mega menu item with a featured image', function () {
    $media = Media::factory()->create();

    MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'Shop'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/shop',
        'is_mega_menu' => true,
        'featured_image_id' => $media->id,
        'featured_title' => ['en' => 'New season audio'],
        'featured_url' => '/deals',
        'is_active' => true,
    ]);

    $result = app(StorefrontHeaderMenuQuery::class)->execute();

    expect($result)->toHaveCount(1)
        ->and($result[0]['featured']['title'])->toBe('New season audio')
        ->and($result[0]['featured']['url'])->toBe('/deals')
        ->and($result[0]['featured']['image'])->not->toBeNull();
});

test('omits featured payload when mega menu item has no featured image', function () {
    MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'Shop'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/shop',
        'is_mega_menu' => true,
        'is_active' => true,
    ]);

    $result = app(StorefrontHeaderMenuQuery::class)->execute();

    expect($result[0])->not->toHaveKey('featured');
});
