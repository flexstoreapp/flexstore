<?php

declare(strict_types=1);

use App\Actions\UpdateMenuItemAction;
use App\DTOs\UpdateMenuItemInput;
use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Models\Category;
use App\Models\Media;
use App\Models\MenuItem;

covers(UpdateMenuItemAction::class, UpdateMenuItemInput::class);

uses()->group('actions', 'menu-item');

test('updates all menu item fields', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();

    $menuItem = MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => 'Old Label',
        'link_type' => MenuItemLinkType::Category,
        'category_id' => $category1->id,
        'url' => null,
        'target' => '_self',
        'parent_id' => null,
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $data = [
        'location' => MenuLocation::Footer->value,
        'label' => 'New Label',
        'link_type' => MenuItemLinkType::Custom->value,
        'category_id' => null,
        'url' => 'https://example.com',
        'target' => '_blank',
        'parent_id' => null,
        'sort_order' => 5,
        'is_active' => false,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result)->toBeInstanceOf(MenuItem::class)
        ->and($result->location)->toBe(MenuLocation::Footer)
        ->and($result->label)->toBe('New Label')
        ->and($result->link_type)->toBe(MenuItemLinkType::Custom)
        ->and($result->category_id)->toBeNull()
        ->and($result->url)->toBe('https://example.com')
        ->and($result->target)->toBe('_blank')
        ->and($result->sort_order)->toBe(5)
        ->and($result->is_active)->toBeFalse();
});

test('updates only label field', function () {
    $menuItem = MenuItem::factory()->create([
        'label' => 'Old Label',
        'link_type' => MenuItemLinkType::Custom,
        'url' => 'https://old.com',
        'target' => '_self',
    ]);

    $data = [
        'label' => 'New Label',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->label)->toBe('New Label')
        ->and($result->link_type)->toBe(MenuItemLinkType::Custom)
        ->and($result->url)->toBe('https://old.com')
        ->and($result->target)->toBe('_self');
});

test('updates link type from category to custom', function () {
    $category = Category::factory()->create();

    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Category,
        'category_id' => $category->id,
        'url' => null,
    ]);

    $data = [
        'link_type' => MenuItemLinkType::Custom->value,
        'category_id' => null,
        'url' => 'https://example.com',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->link_type)->toBe(MenuItemLinkType::Custom)
        ->and($result->category_id)->toBeNull()
        ->and($result->url)->toBe('https://example.com');
});

test('updates link type from custom to category', function () {
    $category = Category::factory()->create();

    $menuItem = MenuItem::factory()->create([
        'link_type' => MenuItemLinkType::Custom,
        'category_id' => null,
        'url' => 'https://example.com',
    ]);

    $data = [
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => $category->id,
        'url' => null,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->link_type)->toBe(MenuItemLinkType::Category)
        ->and($result->category_id)->toBe($category->id)
        ->and($result->url)->toBeNull();
});

test('updates parent id', function () {
    $parent = MenuItem::factory()->create();
    $menuItem = MenuItem::factory()->create(['parent_id' => null]);

    $data = [
        'parent_id' => $parent->id,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->parent_id)->toBe($parent->id);
});

test('removes parent id', function () {
    $parent = MenuItem::factory()->create();
    $menuItem = MenuItem::factory()->create(['parent_id' => $parent->id]);

    $data = [
        'parent_id' => null,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->parent_id)->toBeNull();
});

test('updates is_active status', function () {
    $menuItem = MenuItem::factory()->active()->create();

    $data = [
        'is_active' => false,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->is_active)->toBeFalse();
});

test('updates target attribute', function () {
    $menuItem = MenuItem::factory()->create(['target' => '_self']);

    $data = [
        'target' => '_blank',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->target)->toBe('_blank');
});

test('updates sort order', function () {
    $menuItem = MenuItem::factory()->create(['sort_order' => 0]);

    $data = [
        'sort_order' => 10,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->sort_order)->toBe(10);
});

test('preserves existing values when not provided in data', function () {
    $category = Category::factory()->create();

    $menuItem = MenuItem::factory()->create([
        'location' => MenuLocation::Header,
        'label' => 'Original Label',
        'link_type' => MenuItemLinkType::Category,
        'category_id' => $category->id,
        'target' => '_blank',
        'sort_order' => 5,
        'is_active' => true,
    ]);

    $data = [];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->location)->toBe(MenuLocation::Header)
        ->and($result->label)->toBe('Original Label')
        ->and($result->link_type)->toBe(MenuItemLinkType::Category)
        ->and($result->category_id)->toBe($category->id)
        ->and($result->target)->toBe('_blank')
        ->and($result->sort_order)->toBe(5)
        ->and($result->is_active)->toBeTrue();
});

test('returns the same model instance', function () {
    $menuItem = MenuItem::factory()->create();

    $data = [
        'label' => 'Updated Label',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->is($menuItem))->toBeTrue();
});

test('enables is_mega_menu', function () {
    $menuItem = MenuItem::factory()->create(['is_mega_menu' => false]);

    $data = [
        'is_mega_menu' => true,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->is_mega_menu)->toBeTrue();
});

test('disables is_mega_menu', function () {
    $menuItem = MenuItem::factory()->create(['is_mega_menu' => true]);

    $data = [
        'is_mega_menu' => false,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->is_mega_menu)->toBeFalse();
});

test('preserves is_mega_menu when not provided in data', function () {
    $menuItem = MenuItem::factory()->create(['is_mega_menu' => true]);

    $data = [
        'label' => 'New Label',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->is_mega_menu)->toBeTrue();
});

test('updates featured media', function () {
    $menuItem = MenuItem::factory()->create();
    $media = Media::factory()->create();

    $data = [
        'featured_image_id' => $media->id,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->featured_image_id)->toBe($media->id)
        ->and($result->featuredImage?->url)->toBe($media->url);
});

test('clears featured media', function () {
    $media = Media::factory()->create();
    $menuItem = MenuItem::factory()->create(['featured_image_id' => $media->id]);

    $data = [
        'featured_image_id' => null,
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->featured_image_id)->toBeNull()
        ->and($result->featuredImage)->toBeNull();
});

test('updates featured title and link', function () {
    $menuItem = MenuItem::factory()->create();

    $data = [
        'featured_title' => 'Winter drop',
        'featured_url' => '/collections/winter',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->featured_title)->toBe('Winter drop')
        ->and($result->featured_url)->toBe('/collections/winter');
});

test('preserves featured title and link when not provided in data', function () {
    $menuItem = MenuItem::factory()->create([
        'featured_title' => ['en' => 'Original title'],
        'featured_url' => '/original',
    ]);

    $data = [
        'label' => 'New Label',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->featured_title)->toBe('Original title')
        ->and($result->featured_url)->toBe('/original');
});

test('preserves featured media when not provided in data', function () {
    $media = Media::factory()->create();
    $menuItem = MenuItem::factory()->create(['featured_image_id' => $media->id]);

    $data = [
        'label' => 'New Label',
    ];

    $action = app(UpdateMenuItemAction::class);
    $result = $action->handle($menuItem, UpdateMenuItemInput::fromArray($data));

    expect($result->featured_image_id)->toBe($media->id)
        ->and($result->featuredImage?->url)->toBe($media->url);
});
