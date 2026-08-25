<?php

declare(strict_types=1);

use App\Actions\ReorderMenuItemAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\MenuItemReorderController;
use App\Http\Requests\Admin\ReorderMenuItemRequest;
use App\Models\MenuItem;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\patch;

covers(MenuItemReorderController::class, ReorderMenuItemRequest::class, ReorderMenuItemAction::class);

uses()->group('menu-item');

test('reorders menu item to new parent successfully', function () {
    $oldParent = MenuItem::factory()->create();
    $newParent = MenuItem::factory()->create();
    $menuItem = MenuItem::factory()->create(['parent_id' => $oldParent->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => $newParent->id,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->parent_id)->toBe($newParent->id);
});

test('reorders menu item to root level', function () {
    $parent = MenuItem::factory()->create();
    $menuItem = MenuItem::factory()->create(['parent_id' => $parent->id]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $menuItem->refresh();
    expect($menuItem->parent_id)->toBeNull();
});

test('reorders menu item within same parent', function () {
    $parent = MenuItem::factory()->create();
    $child1 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 0]);
    $child2 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 1]);
    $child3 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 2]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $child3), [
        'parent_id' => $parent->id,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $children = MenuItem::query()
        ->where('parent_id', $parent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children->pluck('id')->toArray())->toBe([
        $child3->id,
        $child1->id,
        $child2->id,
    ]);
});

test('validates position is required', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => null,
    ]);

    $response->assertRedirect()
        ->assertInvalid('position');
});

test('validates position must be non-negative', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => null,
        'position' => -1,
    ]);

    $response->assertRedirect()
        ->assertInvalid('position');
});

test('validates parent_id must exist', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => 99999,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertInvalid('parent_id');
});

test('validates parent_id cannot be itself', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => $menuItem->id,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertInvalid('parent_id');
});

test('validates parent_id depth with MenuItemMaxDepthRule', function () {
    $root = MenuItem::factory()->create();
    $level2 = MenuItem::factory()->create(['parent_id' => $root->id]);
    $level3 = MenuItem::factory()->create(['parent_id' => $level2->id]);
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => $level3->id,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertInvalid('parent_id');
});

test('updates sort order correctly for siblings', function () {
    $parent = MenuItem::factory()->create();
    $child1 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 0]);
    $child2 = MenuItem::factory()->create(['parent_id' => $parent->id, 'location' => $parent->location, 'sort_order' => 1]);

    $newMenuItem = MenuItem::factory()->create(['parent_id' => null, 'location' => $parent->location]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.menu-items.reorder', $newMenuItem), [
        'parent_id' => $parent->id,
        'position' => 1,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $children = MenuItem::query()
        ->where('parent_id', $parent->id)
        ->orderBy('sort_order')
        ->get();

    expect($children)->toHaveCount(3);
    expect($children->pluck('id')->toArray())->toBe([
        $child1->id,
        $newMenuItem->id,
        $child2->id,
    ]);
});

test('requires authentication', function () {
    $menuItem = MenuItem::factory()->create();

    $response = patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $menuItem = MenuItem::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.storefront.menu-items.reorder', $menuItem), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertRedirect();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $anotherMenuItem = MenuItem::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.storefront.menu-items.reorder', $anotherMenuItem), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertForbidden();
});
