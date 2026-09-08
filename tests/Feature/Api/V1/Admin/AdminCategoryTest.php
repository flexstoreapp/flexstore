<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\CategoryReorderController;
use App\Http\Controllers\Api\V1\Admin\CategorySearchController;
use App\Http\Requests\Api\V1\Admin\SearchTermRequest;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Resources\Api\V1\Admin\CategoryOptionResource;
use App\Http\Resources\Api\V1\Admin\CategoryResource;
use App\Http\Resources\Api\V1\Admin\CategoryTreeResource;
use App\Models\Category;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(
    CategoryController::class,
    CategoryReorderController::class,
    CategorySearchController::class,
    StoreCategoryRequest::class,
    SearchTermRequest::class,
    CategoryResource::class,
    CategoryTreeResource::class,
    CategoryOptionResource::class,
);

uses()->group('api', 'admin-api');

test('the category index returns the tree with nested children', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesView]));

    $parent = Category::factory()->create(['name' => 'Parent', 'sort_order' => 0]);
    $child = Category::factory()->create(['name' => 'Child']);
    $child->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    getJson('/api/v1/admin/categories')
        ->assertOk()
        ->assertJsonPath('0.id', $parent->id)
        ->assertJsonPath('0.name', 'Parent')
        ->assertJsonPath('0.children.0.id', $child->id)
        ->assertJsonPath('0.children.0.name', 'Child')
        ->assertJsonStructure([['id', 'name', 'url_handle', 'parent_id', 'sort_order', 'is_active', 'children']]);
});

test('a staff member without the view permission cannot list categories', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson('/api/v1/admin/categories')->assertForbidden();
});

test('a single category is returned with the flat resource shape', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesView]));

    $parent = Category::factory()->create(['name' => 'Parent']);
    $category = Category::factory()->create(['name' => 'Camping', 'is_active' => true]);
    $category->update(['parent_id' => $parent->id, 'sort_order' => 3]);

    getJson('/api/v1/admin/categories/' . $category->id)
        ->assertOk()
        ->assertJsonPath('id', $category->id)
        ->assertJsonPath('name', 'Camping')
        ->assertJsonPath('parent_id', $parent->id)
        ->assertJsonPath('sort_order', 3)
        ->assertJsonPath('is_active', true)
        ->assertJsonMissingPath('children');
});

test('a staff member without the view permission cannot read a single category', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    $category = Category::factory()->create();

    getJson('/api/v1/admin/categories/' . $category->id)->assertForbidden();
});

test('reading a missing category returns not found', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesView]));

    getJson('/api/v1/admin/categories/999999')->assertNotFound();
});

test('a category is created', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesManage]));

    postJson('/api/v1/admin/categories', [
        'name' => 'Outdoor Gear',
        'description' => 'Everything outdoors',
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'Outdoor Gear')
        ->assertJsonPath('url_handle', 'outdoor-gear')
        ->assertJsonPath('is_active', true);

    assertDatabaseHas('categories', ['url_handle' => 'outdoor-gear']);
});

test('creating a category validates the payload', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesManage]));

    postJson('/api/v1/admin/categories', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'is_active']);
});

test('a category is updated with patch semantics', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesManage]));

    $category = Category::factory()->create(['name' => 'Original', 'is_active' => true]);

    patchJson('/api/v1/admin/categories/' . $category->id, ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('name', 'Renamed')
        ->assertJsonPath('is_active', true);

    expect($category->refresh()->name)->toBe('Renamed');
});

test('a category is deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesDelete]));

    $category = Category::factory()->create();

    deleteJson('/api/v1/admin/categories/' . $category->id)->assertOk();

    assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('a category is reordered under a new parent', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesManage]));

    $newParent = Category::factory()->create(['name' => 'New Parent']);
    $category = Category::factory()->create(['name' => 'Moving']);

    patchJson('/api/v1/admin/categories/' . $category->id . '/reorder', [
        'parent_id' => $newParent->id,
        'position' => 0,
    ])
        ->assertOk()
        ->assertJsonPath('0.children.0.id', $category->id);

    expect($category->refresh()->parent_id)->toBe($newParent->id);
});

test('a category cannot be reordered under itself', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesManage]));

    $category = Category::factory()->create();

    patchJson('/api/v1/admin/categories/' . $category->id . '/reorder', [
        'parent_id' => $category->id,
        'position' => 0,
    ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
});

test('the category search returns matching active categories', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CategoriesReference]));

    $match = Category::factory()->create(['name' => 'Camping', 'is_active' => true]);
    Category::factory()->create(['name' => 'Kitchen', 'is_active' => true]);

    getJson('/api/v1/admin/categories/search?query=Camp')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('data.0.name', 'Camping');
});
