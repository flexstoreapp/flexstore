<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\Category;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

covers(CategoryController::class, StoreCategoryRequest::class);

uses()->group('category');

test('displays category list page with categories', function () {
    Category::factory()->count(2)->create();

    $response = actingAsSuperAdmin()->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/categories/list')
                ->has('categories', 2)
        );
});

test('creates a new category', function () {
    $parent = Category::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.categories.store'), [
        'name' => 'New Category',
        'url_handle' => 'new-category',
        'description' => 'Test description',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'name' => castAsTranslatableJson('New Category'),
        'url_handle' => 'new-category',
        'description' => castAsTranslatableJson('Test description'),
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);
});

test('creates a new category with parent relationship', function () {
    $parent = Category::factory()->create();

    $response = actingAsSuperAdmin()
        ->post(route('admin.categories.store'), [
            'name' => 'Child Category',
            'url_handle' => 'child-category',
            'description' => 'Test description',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $category = Category::query()->whereJsonContainsLocale('name', app()->getLocale(), 'Child Category')->first();

    assertDatabaseHas('categories', [
        'name' => castAsTranslatableJson('Child Category'),
        'url_handle' => 'child-category',
        'parent_id' => $parent->id,
    ]);

    expect($category->parent_id)->toBe($parent->id);
    expect($parent->isAncestorOf($category))->toBeTrue();
});

test('creates a category with SEO fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.categories.store'), [
        'name' => 'SEO Category',
        'url_handle' => 'seo-category',
        'seo_title' => 'Best SEO Category',
        'seo_description' => 'Shop the best SEO category products.',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'url_handle' => 'seo-category',
        'seo_title' => castAsTranslatableJson('Best SEO Category'),
        'seo_description' => castAsTranslatableJson('Shop the best SEO category products.'),
    ]);
});

test('validates unique URL handle when creating a category', function () {
    Category::factory()->create(['url_handle' => 'existing-url-handle']);

    $response = actingAsSuperAdmin()->post(route('admin.categories.store'), [
        'name' => 'New Category',
        'url_handle' => 'existing-url-handle', // Already exists
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('categories', [
        'url_handle' => 'New Category',
    ]);
});

test('automatically generates URL handle from category name if URL handle is not provided when creating a new category', function () {
    $response = actingAsSuperAdmin()->post(route('admin.categories.store'), [
        'name' => 'New Test Category',
        // No URL handle provided
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'name' => castAsTranslatableJson('New Test Category'),
        'url_handle' => 'new-test-category',
    ]);
});

test('requires authentication', function () {
    $response = post(route('admin.categories.store'), [
        'name' => 'Test Category',
        'url_handle' => 'test-category',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires categories.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->post(route('admin.categories.store'), [
        'name' => 'Test Category',
        'url_handle' => 'test-category',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'url_handle' => 'test-category',
    ]);

    $role->revokePermissionTo(Permission::CategoriesManage);

    $response = actingAsAdmin()->post(route('admin.categories.store'), [
        'name' => 'Another Category',
        'url_handle' => 'another-category',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('categories', [
        'url_handle' => 'another-category',
    ]);
});

test('rejects a category URL handle that is not a valid slug', function (string $urlHandle) {
    $response = actingAsSuperAdmin()->post(route('admin.categories.store'), [
        'name' => 'Sluggish Category',
        'url_handle' => $urlHandle,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('categories', [
        'url_handle' => $urlHandle,
    ]);
})->with(['Sluggish Category', 'sluggish category', 'sluggish,category', 'sluggish/category', '-sluggish-category', 'sluggish--category']);

test('accepts a category URL handle that is a valid slug', function () {
    $response = actingAsSuperAdmin()->post(route('admin.categories.store'), [
        'name' => 'Sluggish Category',
        'url_handle' => 'sluggish-category-2',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'url_handle' => 'sluggish-category-2',
    ]);
});

test('creates a category with a google product category', function () {
    $response = actingAsSuperAdmin()->post(route('admin.categories.store'), [
        'name' => 'Apparel',
        'url_handle' => 'apparel',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('categories', [
        'url_handle' => 'apparel',
    ]);
});
