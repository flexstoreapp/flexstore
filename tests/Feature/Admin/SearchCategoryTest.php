<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\CategorySearchController;
use App\Models\Category;
use App\Queries\CategorySearchQuery;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

covers(CategorySearchController::class, CategorySearchQuery::class);

uses()->group('category');

test('returns categories without filters', function () {
    Category::factory()->active()->count(20)->create();

    $response = actingAsSuperAdmin()->getJson(route('admin.categories.search'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'name']],
            'next_page_url',
        ]);
});

test('returns categories filtered by query term', function () {
    Category::factory()->active()->create(['name' => 'Test Category']);
    Category::factory()->active()->create(['name' => 'Another Category']);

    $response = actingAsSuperAdmin()->getJson(route('admin.categories.search', ['query' => 'Test']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('Test Category'));
});

test('returns categories filtered by URL handle', function () {
    Category::factory()->active()->create(['name' => 'Electronics', 'url_handle' => 'electronics']);
    Category::factory()->active()->create(['name' => 'Clothing', 'url_handle' => 'clothing']);

    $response = actingAsSuperAdmin()->getJson(route('admin.categories.search', ['query' => 'electronics']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('Electronics'));
});

test('returns active categories when is_active filter is true', function () {
    Category::factory()->active()->create(['name' => 'Active Category', 'is_active' => true]);
    Category::factory()->active()->create(['name' => 'Inactive Category', 'is_active' => false]);

    $response = actingAsSuperAdmin()->getJson(route('admin.categories.search', ['is_active' => 'true']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('Active Category'));
});

test('combines multiple filters correctly', function () {
    Category::factory()->active()->create([
        'name' => 'New Active',
    ]);

    Category::factory()->inactive()->create([
        'name' => 'New Inactive',
    ]);

    Category::factory()->active()->create([
        'name' => 'Old Active',
    ]);

    $response = actingAsSuperAdmin()->getJson(route('admin.categories.search', [
        'query' => 'New',
        'is_active' => 'true',
        'sort' => 'name',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('New Active'));
});

test('requires authentication', function () {
    Category::factory()->active()->count(2)->create();

    $response = getJson(route('admin.categories.search'));

    $response->assertUnauthorized();
});

test('requires category reference access', function () {
    Category::factory()->active()->count(2)->create();

    actingAs(userWithPermissions([Permission::CategoriesView]))
        ->getJson(route('admin.categories.search'))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    actingAs(userWithPermissions([Permission::DashboardView]))
        ->getJson(route('admin.categories.search'))
        ->assertForbidden();
});
