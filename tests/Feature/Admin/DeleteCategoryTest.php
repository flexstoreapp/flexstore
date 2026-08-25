<?php

declare(strict_types=1);

use App\Actions\DestroyCategoryAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CategoryController;
use App\Models\Category;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(CategoryController::class, DestroyCategoryAction::class);

uses()->group('category');

test('can delete a category', function () {
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->delete(route('admin.categories.destroy', $category));

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('deletes category with children', function () {
    $parent = Category::factory()->create();
    $child1 = Category::factory()->create(['parent_id' => $parent->id]);
    $child2 = Category::factory()->create(['parent_id' => $parent->id]);
    $grandchild = Category::factory()->create(['parent_id' => $child1->id]);

    $response = actingAsSuperAdmin()->delete(route('admin.categories.destroy', $parent));

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('categories', ['id' => $parent->id]);
    assertDatabaseMissing('categories', ['id' => $child1->id]);
    assertDatabaseMissing('categories', ['id' => $child2->id]);
    assertDatabaseMissing('categories', ['id' => $grandchild->id]);
});

test('returns 404 for non-existent category', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.categories.destroy', 999));

    $response->assertNotFound();
});

test('requires authentication', function () {
    $category = Category::factory()->create();

    $response = delete(route('admin.categories.destroy', $category));

    $response->assertRedirect(route('admin.login'));
});

test('requires categories.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $category = Category::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.categories.destroy', $category));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);

    $role->revokePermissionTo(Permission::CategoriesDelete);

    $anotherCategory = Category::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.categories.destroy', $anotherCategory));

    $response->assertForbidden();

    assertDatabaseHas('categories', [
        'id' => $anotherCategory->id,
    ]);
});
