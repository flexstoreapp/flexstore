<?php

declare(strict_types=1);

use App\Actions\DestroyMenuItemAction;
use App\Models\Media;
use App\Models\MenuItem;

covers(DestroyMenuItemAction::class);

uses()->group('actions', 'menu-item');

test('successfully deletes menu item', function () {
    $menuItem = MenuItem::factory()->create();

    $action = new DestroyMenuItemAction();
    $result = $action->handle($menuItem);

    expect($result)->toBeTrue();
    expect(MenuItem::query()->find($menuItem->id))->toBeNull();
});

test('deletes menu item and its children', function () {
    $parent = MenuItem::factory()->create();

    $child1 = MenuItem::factory()->create(['parent_id' => $parent->id]);
    $child2 = MenuItem::factory()->create(['parent_id' => $parent->id]);

    $action = new DestroyMenuItemAction();
    $result = $action->handle($parent);

    expect($result)->toBeTrue();
    expect(MenuItem::query()->find($parent->id))->toBeNull();
    expect(MenuItem::query()->find($child1->id))->toBeNull();
    expect(MenuItem::query()->find($child2->id))->toBeNull();
});

test('deletes menu item and its direct children only', function () {
    $parent = MenuItem::factory()->create();
    $child = MenuItem::factory()->create(['parent_id' => $parent->id]);
    $grandchild = MenuItem::factory()->create(['parent_id' => $child->id]);

    $action = new DestroyMenuItemAction();
    $result = $action->handle($parent);

    expect($result)->toBeTrue();
    expect(MenuItem::query()->find($parent->id))->toBeNull();
    expect(MenuItem::query()->find($child->id))->toBeNull();
    expect(MenuItem::query()->find($grandchild->id))->not->toBeNull();
});

test('does not delete sibling menu items', function () {
    $parent = MenuItem::factory()->create();

    $child1 = MenuItem::factory()->create(['parent_id' => $parent->id]);
    $child2 = MenuItem::factory()->create(['parent_id' => $parent->id]);

    $action = new DestroyMenuItemAction();
    $action->handle($child1);

    expect(MenuItem::query()->find($child1->id))->toBeNull();
    expect(MenuItem::query()->find($child2->id))->not->toBeNull();
    expect(MenuItem::query()->find($parent->id))->not->toBeNull();
});

test('deletes menu item without children', function () {
    $menuItem = MenuItem::factory()->create();
    $otherMenuItem = MenuItem::factory()->create();

    $action = new DestroyMenuItemAction();
    $result = $action->handle($menuItem);

    expect($result)->toBeTrue();
    expect(MenuItem::query()->find($menuItem->id))->toBeNull();
    expect(MenuItem::query()->find($otherMenuItem->id))->not->toBeNull();
});

test('deletes featured media that is no longer referenced', function () {
    $media = Media::factory()->create();
    $menuItem = MenuItem::factory()->create(['featured_image_id' => $media->id]);

    (new DestroyMenuItemAction())->handle($menuItem);

    expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
});
