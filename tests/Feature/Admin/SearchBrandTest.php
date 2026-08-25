<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\BrandSearchController;
use App\Models\Brand;
use App\Queries\BrandSearchQuery;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

covers(BrandSearchController::class, BrandSearchQuery::class);

uses()->group('brand');

test('returns paginated brands with correct structure', function () {
    Brand::factory()->active()->count(20)->create();

    $response = actingAsSuperAdmin()->getJson(route('admin.brands.search'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'name', 'url_handle', 'image']],
            'next_page_url',
            'path',
            'per_page',
        ]);
});

test('only returns active brands by default', function () {
    Brand::factory()->active()->create(['name' => 'Active Brand']);
    Brand::factory()->create(['name' => 'Inactive Brand', 'is_active' => false]);

    $response = actingAsSuperAdmin()->getJson(route('admin.brands.search'));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('Active Brand'));
});

test('sorts brands by name alphabetically', function () {
    Brand::factory()->active()->create(['name' => 'Z Brand']);
    Brand::factory()->active()->create(['name' => 'A Brand']);

    $response = actingAsSuperAdmin()->getJson(route('admin.brands.search'));

    $response->assertOk()
        ->assertJsonPath('data.0.name', castAsTranslatableArray('A Brand'))
        ->assertJsonPath('data.1.name', castAsTranslatableArray('Z Brand'));
});

test('filters brands by query term', function () {
    Brand::factory()->active()->create(['name' => 'Nike Shoes']);
    Brand::factory()->active()->create(['name' => 'Adidas Shoes']);
    Brand::factory()->active()->create(['name' => 'Puma Clothing']);

    $response = actingAsSuperAdmin()->getJson(route('admin.brands.search', ['query' => 'Shoes']));

    $response->assertOk()->assertJsonCount(2, 'data');
});

test('filters brands by URL handle', function () {
    Brand::factory()->active()->create(['name' => 'Nike', 'url_handle' => 'nike']);
    Brand::factory()->active()->create(['name' => 'Adidas', 'url_handle' => 'adidas']);

    $response = actingAsSuperAdmin()->getJson(route('admin.brands.search', ['query' => 'nike']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('Nike'));
});

test('search is case insensitive', function () {
    Brand::factory()->active()->create(['name' => 'Nike Brand']);

    $response = actingAsSuperAdmin()->getJson(route('admin.brands.search', ['query' => 'nike']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('Nike Brand'));
});

test('returns empty result when no brands match query', function () {
    Brand::factory()->active()->create(['name' => 'Test Brand']);

    $response = actingAsSuperAdmin()->getJson(route('admin.brands.search', ['query' => 'NonExistent']));

    $response->assertOk()->assertJsonCount(0, 'data');
});

test('requires authentication', function () {
    Brand::factory()->active()->count(2)->create();

    $response = getJson(route('admin.brands.search'));

    $response->assertUnauthorized();
});

test('requires brand reference access', function () {
    Brand::factory()->active()->count(2)->create();

    actingAs(userWithPermissions([Permission::BrandsView]))
        ->getJson(route('admin.brands.search'))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    actingAs(userWithPermissions([Permission::DashboardView]))
        ->getJson(route('admin.brands.search'))
        ->assertForbidden();
});
