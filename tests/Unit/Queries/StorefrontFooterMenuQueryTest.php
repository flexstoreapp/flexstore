<?php

declare(strict_types=1);

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Models\Category;
use App\Models\MenuItem;
use App\Queries\StorefrontFooterMenuQuery;

covers(StorefrontFooterMenuQuery::class);

uses()->group('queries', 'storefront');

test('returns active footer menu items ordered by sort order', function () {
    MenuItem::factory()->create([
        'location' => MenuLocation::Footer,
        'label' => ['en' => 'Second'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/second',
        'sort_order' => 2,
        'is_active' => true,
    ]);
    MenuItem::factory()->create([
        'location' => MenuLocation::Footer,
        'label' => ['en' => 'First'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/first',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    MenuItem::factory()->create([
        'location' => MenuLocation::Footer,
        'label' => ['en' => 'Hidden'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/hidden',
        'sort_order' => 3,
        'is_active' => false,
    ]);

    $result = app(StorefrontFooterMenuQuery::class)->execute();

    expect($result)->toHaveCount(2)
        ->and($result[0]['label'])->toBe('First')
        ->and($result[0]['url'])->toBe('/first')
        ->and($result[1]['label'])->toBe('Second');
});

test('resolves category link url and nests children', function () {
    $category = Category::factory()->create();

    $parent = MenuItem::factory()->create([
        'location' => MenuLocation::Footer,
        'label' => ['en' => 'Company'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/company',
        'is_active' => true,
    ]);

    MenuItem::factory()->create([
        'location' => MenuLocation::Footer,
        'label' => ['en' => 'Category'],
        'link_type' => MenuItemLinkType::Category,
        'category_id' => $category->id,
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $result = app(StorefrontFooterMenuQuery::class)->execute();

    expect($result)->toHaveCount(1)
        ->and($result[0]['label'])->toBe('Company')
        ->and($result[0]['children'])->toHaveCount(1)
        ->and($result[0]['children'][0]['label'])->toBe('Category')
        ->and($result[0]['children'][0]['url'])->toContain($category->url_handle);
});

test('excludes footer items from other locations', function () {
    MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => ['en' => 'Header only'],
        'link_type' => MenuItemLinkType::Custom,
        'url' => '/header',
        'is_active' => true,
    ]);

    $result = app(StorefrontFooterMenuQuery::class)->execute();

    expect($result)->toBeEmpty();
});
