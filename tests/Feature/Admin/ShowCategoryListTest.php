<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CategoryController;
use App\Models\Category;
use App\Queries\CategoryTreeQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(CategoryController::class, CategoryTreeQuery::class);

uses()->group('category');

test('displays category list page', function () {
    Category::factory(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/categories/list')
                ->has('categories', 3)
        );
});

test('returns categories in tree structure with children', function () {

    $parent = Category::factory()->create(['name' => 'Parent Category']);
    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child2 = Category::factory()->create(['name' => 'Child 2']);

    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $response = actingAsSuperAdmin()->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/categories/list')
                ->has('categories', 1)
                ->where('categories.0.name', castAsTranslatableArray('Parent Category'))
                ->has('categories.0.children', 2)
                ->where('categories.0.children.0.name', castAsTranslatableArray('Child 1'))
                ->where('categories.0.children.1.name', castAsTranslatableArray('Child 2'))
        );
});

test('maintains proper tree order with nested categories', function () {

    $root1 = Category::factory()->create(['name' => 'Root 1']);
    $root2 = Category::factory()->create(['name' => 'Root 2']);

    $child1 = Category::factory()->create(['name' => 'Child 1-1']);
    $child2 = Category::factory()->create(['name' => 'Child 1-2']);

    $grandchild = Category::factory()->create(['name' => 'Grandchild 1-1-1']);

    $child1->update(['parent_id' => $root1->id, 'sort_order' => 0]);
    $child2->update(['parent_id' => $root1->id, 'sort_order' => 1]);
    $grandchild->update(['parent_id' => $child1->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/categories/list')
                ->has('categories', 2)
                ->where('categories.0.name', castAsTranslatableArray('Root 1'))
                ->where('categories.1.name', castAsTranslatableArray('Root 2'))
                ->has('categories.0.children', 2)
                ->where('categories.0.children.0.name', castAsTranslatableArray('Child 1-1'))
                ->has('categories.0.children.0.children', 1)
                ->where('categories.0.children.0.children.0.name', castAsTranslatableArray('Grandchild 1-1-1'))
        );
});

test('requires authentication', function () {
    Category::factory()->count(2)->create();

    $response = get(route('admin.categories.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires categories.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    Category::factory()->count(2)->create();

    $response = actingAsAdmin()->get(route('admin.categories.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::CategoriesView);

    $response = actingAsAdmin()->get(route('admin.categories.index'));

    $response->assertForbidden();
});
