<?php

declare(strict_types=1);

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Enums\MenuPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

covers(MenuItem::class);

uses()->group('unit', 'models');

test('has factory', function () {
    $menuItem = MenuItem::factory()->create();

    expect($menuItem)->toBeInstanceOf(MenuItem::class);
});

test('uses HasTranslations trait', function () {
    expect(in_array(HasTranslations::class, class_uses_recursive(MenuItem::class)))->toBeTrue();
});

test('has translatable fields', function () {
    $menuItem = new MenuItem();

    expect($menuItem->getTranslatableAttributes())->toBe(['label', 'featured_title']);
});

test('has correct casts', function () {
    $menuItem = new MenuItem();
    $casts = $menuItem->casts();

    expect($casts)->toBe([
        'location' => MenuLocation::class,
        'link_type' => MenuItemLinkType::class,
        'page' => MenuPage::class,
        'sort_order' => 'integer',
        'is_mega_menu' => 'boolean',
        'is_active' => 'boolean',
    ]);
});

test('casts location to enum', function () {
    $menuItem = MenuItem::factory()->header()->create();

    expect($menuItem->location)->toBe(MenuLocation::Header);
});

test('casts link_type to enum', function () {
    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Page,
    ]);

    expect($menuItem->link_type)->toBe(MenuItemLinkType::Page);
});

test('casts page to enum', function () {
    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Page,
        'page' => MenuPage::Products,
    ]);

    expect($menuItem->page)->toBe(MenuPage::Products);
});

test('parent relationship returns BelongsTo', function () {
    $menuItem = new MenuItem();

    expect($menuItem->parent())->toBeInstanceOf(BelongsTo::class);
});

test('category relationship returns BelongsTo', function () {
    $menuItem = new MenuItem();

    expect($menuItem->category())->toBeInstanceOf(BelongsTo::class);
});

test('children relationship returns HasMany', function () {
    $menuItem = new MenuItem();

    expect($menuItem->children())->toBeInstanceOf(HasMany::class);
});

test('can have parent menu item', function () {
    $parent = MenuItem::factory()->create();
    $child = MenuItem::factory()->create(['parent_id' => $parent->id]);

    expect($child->parent)->toBeInstanceOf(MenuItem::class)
        ->and($child->parent->id)->toBe($parent->id);
});

test('can have children menu items', function () {
    $parent = MenuItem::factory()->create();
    MenuItem::factory()->count(3)->create(['parent_id' => $parent->id]);

    expect($parent->children)->toHaveCount(3);
});

test('children are ordered by sort_order', function () {
    $parent = MenuItem::factory()->create();
    MenuItem::factory()->create(['parent_id' => $parent->id, 'sort_order' => 3]);
    MenuItem::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);
    MenuItem::factory()->create(['parent_id' => $parent->id, 'sort_order' => 2]);

    $parent->refresh();
    $sortOrders = $parent->children->pluck('sort_order')->all();

    expect($sortOrders)->toBe([1, 2, 3]);
});

test('can belong to category', function () {
    $category = Category::factory()->create();
    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Category,
        'category_id' => $category->id,
    ]);

    expect($menuItem->category)->toBeInstanceOf(Category::class)
        ->and($menuItem->category->id)->toBe($category->id);
});

test('forLocation scope filters by location', function () {
    MenuItem::factory()->header()->count(2)->create();
    MenuItem::factory()->footer()->count(3)->create();

    $headerItems = MenuItem::forLocation(MenuLocation::Header)->get();
    $footerItems = MenuItem::forLocation(MenuLocation::Footer)->get();

    expect($headerItems)->toHaveCount(2)
        ->and($footerItems)->toHaveCount(3);
});

test('can belong to brand', function () {
    $brand = Brand::factory()->create();
    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Brand,
        'brand_id' => $brand->id,
    ]);

    expect($menuItem->brand)->toBeInstanceOf(Brand::class)
        ->and($menuItem->brand->id)->toBe($brand->id);
});

test('featured image relation is null when none set', function () {
    $menuItem = MenuItem::factory()->create();

    expect($menuItem->featuredImage)->toBeNull();
});

test('featured image relation returns the linked media', function () {
    $media = Media::factory()->create();
    $menuItem = MenuItem::factory()->create(['featured_image_id' => $media->id]);

    expect($menuItem->featuredImage?->url)->toBe($media->url);
});
