<?php

declare(strict_types=1);

use App\Actions\DestroyMenuItemAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\MenuItemController;
use App\Models\MenuItem;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\delete;

covers(MenuItemController::class, DestroyMenuItemAction::class);

uses()->group('menu-item');

test('deletes menu item successfully', function () {
    $menuItem = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->delete(route('admin.storefront.menu-items.destroy', $menuItem));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(MenuItem::query()->find($menuItem->id))->toBeNull();
});

test('deletes menu item with children', function () {
    $parent = MenuItem::factory()->create();
    $child1 = MenuItem::factory()->create(['parent_id' => $parent->id]);
    $child2 = MenuItem::factory()->create(['parent_id' => $parent->id]);

    $response = actingAsSuperAdmin()->delete(route('admin.storefront.menu-items.destroy', $parent));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(MenuItem::query()->find($parent->id))->toBeNull();
    expect(MenuItem::query()->find($child1->id))->toBeNull();
    expect(MenuItem::query()->find($child2->id))->toBeNull();
});

test('deletes menu item and its direct children only', function () {
    $parent = MenuItem::factory()->create();
    $child = MenuItem::factory()->create(['parent_id' => $parent->id]);
    $grandchild = MenuItem::factory()->create(['parent_id' => $child->id]);

    $response = actingAsSuperAdmin()->delete(route('admin.storefront.menu-items.destroy', $parent));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(MenuItem::query()->find($parent->id))->toBeNull();
    expect(MenuItem::query()->find($child->id))->toBeNull();
    expect(MenuItem::query()->find($grandchild->id))->not->toBeNull();
});

test('does not delete other menu items', function () {
    $menuItem1 = MenuItem::factory()->create();
    $menuItem2 = MenuItem::factory()->create();

    $response = actingAsSuperAdmin()->delete(route('admin.storefront.menu-items.destroy', $menuItem1));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(MenuItem::query()->find($menuItem1->id))->toBeNull();
    expect(MenuItem::query()->find($menuItem2->id))->not->toBeNull();
});

test('requires authentication', function () {
    $menuItem = MenuItem::factory()->create();

    $response = delete(route('admin.storefront.menu-items.destroy', $menuItem));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $menuItem = MenuItem::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.storefront.menu-items.destroy', $menuItem));

    $response->assertRedirect();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $anotherMenuItem = MenuItem::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.storefront.menu-items.destroy', $anotherMenuItem));

    $response->assertForbidden();
});
