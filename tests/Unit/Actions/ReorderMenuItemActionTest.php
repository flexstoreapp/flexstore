<?php

declare(strict_types=1);

use App\Actions\ReorderMenuItemAction;
use App\Enums\MenuLocation;
use App\Models\MenuItem;

covers(ReorderMenuItemAction::class);

uses()->group('actions', 'menu-item');

test('reorders menu item to new parent at first position', function () {
    $oldParent = MenuItem::factory()->create();
    $newParent = MenuItem::factory()->create(['location' => $oldParent->location]);

    $menuItem = MenuItem::factory()->create(['parent_id' => $oldParent->id, 'location' => $oldParent->location, 'sort_order' => 0]);

    $existingChild1 = MenuItem::factory()->create(['parent_id' => $newParent->id, 'location' => $newParent->location, 'sort_order' => 0]);
    $existingChild2 = MenuItem::factory()->create(['parent_id' => $newParent->id, 'location' => $newParent->location, 'sort_order' => 1]);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($menuItem, $newParent->id, 0);

    expect($result)->toBeInstanceOf(MenuItem::class)
        ->and($result->parent_id)->toBe($newParent->id);

    $children = MenuItem::query()
        ->where('parent_id', $newParent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children)->toHaveCount(3);
    expect($children->pluck('id')->toArray())->toBe([
        $menuItem->id,
        $existingChild1->id,
        $existingChild2->id,
    ]);
});

test('reorders menu item to new parent at last position', function () {
    $oldParent = MenuItem::factory()->create();
    $newParent = MenuItem::factory()->create(['location' => $oldParent->location]);

    $menuItem = MenuItem::factory()->create(['parent_id' => $oldParent->id, 'location' => $oldParent->location, 'sort_order' => 0]);

    $existingChild1 = MenuItem::factory()->create(['parent_id' => $newParent->id, 'location' => $newParent->location, 'sort_order' => 0]);
    $existingChild2 = MenuItem::factory()->create(['parent_id' => $newParent->id, 'location' => $newParent->location, 'sort_order' => 1]);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($menuItem, $newParent->id, 2);

    expect($result)->toBeInstanceOf(MenuItem::class)
        ->and($result->parent_id)->toBe($newParent->id);

    $children = MenuItem::query()
        ->where('parent_id', $newParent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children)->toHaveCount(3);
    expect($children->pluck('id')->toArray())->toBe([
        $existingChild1->id,
        $existingChild2->id,
        $menuItem->id,
    ]);
});

test('moves menu item to root level', function () {
    $parent = MenuItem::factory()->create(['parent_id' => null]);
    $menuItem = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 0]);

    $rootItem1 = MenuItem::factory()->create(['parent_id' => null, 'location' => $menuItem->location, 'sort_order' => 0]);
    $rootItem2 = MenuItem::factory()->create(['parent_id' => null, 'location' => $menuItem->location, 'sort_order' => 1]);

    expect($menuItem->parent_id)->toBe($parent->id);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($menuItem, null, 0);

    expect($result)->toBeInstanceOf(MenuItem::class)
        ->and($result->parent_id)->toBeNull();

    $rootItems = MenuItem::query()
        ->whereNull('parent_id')
        ->where('location', $menuItem->location)
        ->orderBy('sort_order')
        ->get();

    expect($rootItems)->toHaveCount(4);
    expect($rootItems->pluck('id')->toArray())->toContain($menuItem->id, $rootItem1->id, $rootItem2->id, $parent->id);
});

test('reorders within same parent moving down', function () {
    $parent = MenuItem::factory()->create();

    $child1 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 0]);
    $child2 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 1]);
    $child3 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 2]);
    $child4 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 3]);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($child1, $parent->id, 2);

    expect($result)->toBeInstanceOf(MenuItem::class);

    $children = MenuItem::query()
        ->where('parent_id', $parent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children)->toHaveCount(4);
    expect($children->pluck('id')->toArray())->toBe([
        $child2->id,
        $child3->id,
        $child1->id,
        $child4->id,
    ]);
});

test('reorders within same parent moving up', function () {
    $parent = MenuItem::factory()->create();

    $child1 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 0]);
    $child2 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 1]);
    $child3 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 2]);
    $child4 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 3]);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($child4, $parent->id, 1);

    expect($result)->toBeInstanceOf(MenuItem::class);

    $children = MenuItem::query()
        ->where('parent_id', $parent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children)->toHaveCount(4);
    expect($children->pluck('id')->toArray())->toBe([
        $child1->id,
        $child4->id,
        $child2->id,
        $child3->id,
    ]);
});

test('reorders root menu items', function () {
    $root1 = MenuItem::factory()->create(['parent_id' => null, 'sort_order' => 0]);
    $root2 = MenuItem::factory()->create(['parent_id' => null, 'location' => $root1->location, 'sort_order' => 1]);
    $root3 = MenuItem::factory()->create(['parent_id' => null, 'location' => $root1->location, 'sort_order' => 2]);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($root3, null, 0);

    expect($result)->toBeInstanceOf(MenuItem::class)
        ->and($result->parent_id)->toBeNull();

    $rootItems = MenuItem::query()
        ->whereNull('parent_id')
        ->where('location', $root1->location)
        ->orderBy('sort_order')
        ->get();

    expect($rootItems)->toHaveCount(3);
    expect($rootItems->pluck('id')->toArray())->toBe([
        $root3->id,
        $root1->id,
        $root2->id,
    ]);
});

test('only reorders items with same location', function () {
    $headerItem = MenuItem::factory()->create(['location' => MenuLocation::Header, 'parent_id' => null, 'sort_order' => 0]);
    $footerItem = MenuItem::factory()->create(['location' => MenuLocation::Footer, 'parent_id' => null, 'sort_order' => 0]);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($headerItem, null, 1);

    $headerItems = MenuItem::query()
        ->whereNull('parent_id')
        ->where('location', MenuLocation::Header)
        ->orderBy('sort_order')
        ->get();

    $footerItems = MenuItem::query()
        ->whereNull('parent_id')
        ->where('location', MenuLocation::Footer)
        ->orderBy('sort_order')
        ->get();

    expect($headerItems)->toHaveCount(1);
    expect($footerItems)->toHaveCount(1);
});

test('handles moving to empty parent', function () {
    $parent = MenuItem::factory()->create();
    $menuItem = MenuItem::factory()->create(['parent_id' => null, 'sort_order' => 0]);

    $action = new ReorderMenuItemAction();
    $result = $action->handle($menuItem, $parent->id, 0);

    expect($result)->toBeInstanceOf(MenuItem::class)
        ->and($result->parent_id)->toBe($parent->id);

    $children = MenuItem::query()
        ->where('parent_id', $parent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children)->toHaveCount(1);
    expect($children->first()->id)->toBe($menuItem->id);
});

test('returns fresh model instance', function () {
    $menuItem = MenuItem::factory()->create(['sort_order' => 5]);
    $parent = MenuItem::factory()->create();

    $action = new ReorderMenuItemAction();
    $result = $action->handle($menuItem, $parent->id, 0);

    expect($result)->toBeInstanceOf(MenuItem::class);
    expect($result->sort_order)->toBe(0);
    expect($result->parent_id)->toBe($parent->id);
});
