<?php

declare(strict_types=1);

use App\Actions\UpdateCategoryAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Queries\CategoryTreeQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\patch;

covers(CategoryController::class, UpdateCategoryRequest::class, UpdateCategoryAction::class, CategoryTreeQuery::class);

uses()->group('category');

test('displays category list page with categories for editing', function () {
    $parent = Category::factory()->create();
    $category = Category::factory()->create();
    $category->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/categories/list')
                ->has('categories')
        );
});

test('updates category SEO fields', function () {
    $category = Category::factory()->create([
        'seo_title' => 'Old SEO Title',
        'seo_description' => 'Old SEO description',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.update', $category), [
        'seo_title' => 'New SEO Title',
        'seo_description' => 'New SEO description',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'seo_title' => castAsTranslatableJson('New SEO Title'),
        'seo_description' => castAsTranslatableJson('New SEO description'),
    ]);
});

test('updates an existing category', function () {
    $category = Category::factory()->create([
        'name' => 'Old Name',
        'url_handle' => 'old-url-handle',
        'is_active' => false,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.update', $category), [
        'name' => 'Updated Name',
        'url_handle' => 'updated-url-handle',
        'description' => 'Updated description',
        'parent_id' => null,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => castAsTranslatableJson('Updated Name'),
        'url_handle' => 'updated-url-handle',
        'description' => castAsTranslatableJson('Updated description'),
        'is_active' => true,
    ]);
});

test('category cannot be its own parent', function () {
    $category = Category::factory()->create([
        'name' => 'Original Category',
        'url_handle' => 'original-category',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.update', $category), [
        'name' => 'Updated Category',
        'parent_id' => $category->id, // Trying to set itself as parent
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('parent_id');

    assertDatabaseMissing('categories', [
        'id' => $category->id,
        'parent_id' => $category->id,
    ]);
});

test('prevents moving category to its own descendant', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create();
    $child->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $grandchild = Category::factory()->create();
    $grandchild->update(['parent_id' => $child->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.update', $parent), [
        'name' => 'Updated',
        'parent_id' => $grandchild->id,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('parent_id');

    $parent->refresh();
    expect($parent->parent_id)->toBeNull();
});

test('validates unique URL handle when updating a category', function () {
    $categoryToUpdate = Category::factory()->create();

    Category::factory()->create(['url_handle' => 'existing-url-handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.update', $categoryToUpdate), [
        'name' => 'Updated Name',
        'url_handle' => 'existing-url-handle', // Already exists
        'description' => 'Updated description',
        'parent_id' => null,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('categories', [
        'id' => $categoryToUpdate->id,
        'url_handle' => 'existing-url-handle',
    ]);
});

test('requires authentication', function () {
    $category = Category::factory()->create();

    $response = patch(route('admin.categories.update', $category), [
        'name' => 'Updated Name',
        'url_handle' => 'updated-url-handle',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires categories.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $category = Category::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.categories.update', $category), [
        'name' => 'Updated Name',
        'url_handle' => 'updated-url-handle',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'url_handle' => 'updated-url-handle',
    ]);

    $role->revokePermissionTo(Permission::CategoriesManage);

    $anotherCategory = Category::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.categories.update', $anotherCategory), [
        'name' => 'Another Update',
        'url_handle' => 'another-update',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('categories', [
        'url_handle' => 'another-update',
    ]);
});

test('rejects a category URL handle that is not a valid slug', function () {
    $category = Category::factory()->create(['url_handle' => 'valid-handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.update', $category), [
        'url_handle' => 'Invalid Handle',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'url_handle' => 'valid-handle',
    ]);
});

test('leaves an existing invalid category URL handle untouched when it is not submitted', function () {
    $category = Category::factory()->create(['url_handle' => 'Legacy Handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.update', $category), [
        'seo_title' => 'New SEO Title',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'id' => $category->id,
        'url_handle' => 'Legacy Handle',
    ]);
});
