<?php

declare(strict_types=1);

use App\Actions\StoreMenuItemAction;
use App\DTOs\StoreMenuItemInput;
use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Models\Category;
use App\Models\Media;
use App\Models\MenuItem;

covers(StoreMenuItemAction::class, StoreMenuItemInput::class);

uses()->group('actions', 'menu-item');

test('creates menu item with all fields', function () {
    $category = Category::factory()->create();

    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Menu Item',
        'link_type' => MenuItemLinkType::Category->value,
        'category_id' => $category->id,
        'url' => null,
        'target' => '_blank',
        'parent_id' => null,
        'is_active' => true,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem)->toBeInstanceOf(MenuItem::class)
        ->and($menuItem->location)->toBe(MenuLocation::Header)
        ->and($menuItem->label)->toBe('Test Menu Item')
        ->and($menuItem->link_type)->toBe(MenuItemLinkType::Category)
        ->and($menuItem->category_id)->toBe($category->id)
        ->and($menuItem->url)->toBeNull()
        ->and($menuItem->target)->toBe('_blank')
        ->and($menuItem->parent_id)->toBeNull()
        ->and($menuItem->is_active)->toBeTrue();
});

test('creates menu item with custom url', function () {
    $data = [
        'location' => MenuLocation::Footer->value,
        'label' => 'External Link',
        'link_type' => MenuItemLinkType::Custom->value,
        'category_id' => null,
        'url' => 'https://example.com',
        'target' => '_blank',
        'parent_id' => null,
        'is_active' => true,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem)->toBeInstanceOf(MenuItem::class)
        ->and($menuItem->link_type)->toBe(MenuItemLinkType::Custom)
        ->and($menuItem->url)->toBe('https://example.com')
        ->and($menuItem->category_id)->toBeNull();
});

test('sets default target to _self when not provided', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->target)->toBe('_self');
});

test('sets is_active to true by default', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Test Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->is_active)->toBeTrue();
});

test('creates menu item with parent', function () {
    $parent = MenuItem::factory()->create();

    $data = [
        'location' => $parent->location->value,
        'label' => 'Child Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => $parent->id,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->parent_id)->toBe($parent->id);
});

test('assigns sort order as max plus one for same location and parent', function () {
    $parent = MenuItem::factory()->create();

    MenuItem::factory()->create([
        'parent_id' => $parent->id,
        'location' => $parent->location,
        'sort_order' => 0,
    ]);

    MenuItem::factory()->create([
        'parent_id' => $parent->id,
        'location' => $parent->location,
        'sort_order' => 1,
    ]);

    $data = [
        'location' => $parent->location->value,
        'label' => 'New Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => $parent->id,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->sort_order)->toBe(2);
});

test('assigns sort order zero when no existing items with same location and parent', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'First Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => null,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->sort_order)->toBe(0);
});

test('assigns correct sort order for root level items', function () {
    MenuItem::factory()->create([
        'parent_id' => null,
        'location' => MenuLocation::Header,
        'sort_order' => 0,
    ]);

    MenuItem::factory()->create([
        'parent_id' => null,
        'location' => MenuLocation::Header,
        'sort_order' => 1,
    ]);

    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'New Root Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => null,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->sort_order)->toBe(2);
});

test('ignores sort order from different locations', function () {
    MenuItem::factory()->create([
        'parent_id' => null,
        'location' => MenuLocation::Footer,
        'sort_order' => 5,
    ]);

    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Header Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => null,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->sort_order)->toBe(0);
});

test('ignores sort order from different parents', function () {
    $parent1 = MenuItem::factory()->create();
    $parent2 = MenuItem::factory()->create(['location' => $parent1->location]);

    MenuItem::factory()->create([
        'parent_id' => $parent1->id,
        'location' => $parent1->location,
        'sort_order' => 3,
    ]);

    $data = [
        'location' => $parent2->location->value,
        'label' => 'Child of Parent 2',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'parent_id' => $parent2->id,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->sort_order)->toBe(0);
});

test('creates inactive menu item when specified', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Inactive Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_active' => false,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->is_active)->toBeFalse();
});

test('creates menu item with is_mega_menu enabled', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Mega Menu Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_mega_menu' => true,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->is_mega_menu)->toBeTrue();
});

test('sets is_mega_menu to false by default', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Regular Menu Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->is_mega_menu)->toBeFalse();
});

test('creates menu item with featured media', function () {
    $media = Media::factory()->create();

    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Menu With Image',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_mega_menu' => true,
        'featured_image_id' => $media->id,
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->featured_image_id)->toBe($media->id)
        ->and($menuItem->featuredImage?->url)->toBe($media->url);
});

test('creates menu item with featured title and link', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Mega Menu',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
        'is_mega_menu' => true,
        'featured_title' => 'New season audio',
        'featured_url' => '/collections/audio',
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->featured_title)->toBe('New season audio')
        ->and($menuItem->featured_url)->toBe('/collections/audio');
});

test('sets featured title and link to null by default', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Menu Item',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->featured_title)->toBe('')
        ->and($menuItem->featured_url)->toBeNull();
});

test('sets featured media to empty by default', function () {
    $data = [
        'location' => MenuLocation::Header->value,
        'label' => 'Menu Without Image',
        'link_type' => MenuItemLinkType::Custom->value,
        'url' => 'https://example.com',
    ];

    $action = app(StoreMenuItemAction::class);
    $menuItem = $action->handle(StoreMenuItemInput::fromArray($data));

    expect($menuItem->featured_image_id)->toBeNull()
        ->and($menuItem->featuredImage)->toBeNull();
});
