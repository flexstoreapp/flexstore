<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\RegionSearchController;
use App\Models\Region;
use App\Queries\RegionSearchQuery;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

covers(RegionSearchController::class, RegionSearchQuery::class);

uses()->group('filters', 'region');

test('returns regions filtered by query parameter', function () {
    Region::factory()->active()->create(['name' => 'United States']);
    Region::factory()->active()->create(['name' => 'European Union']);
    Region::factory()->active()->create(['name' => 'United Kingdom']);

    $response = actingAsSuperAdmin()->getJson(route('admin.regions.search', ['query' => 'united']));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('United Kingdom'))
        ->assertJsonPath('data.1.name', castAsTranslatableArray('United States'));
});

test('returns regions matching partial text in name', function () {
    Region::factory()->active()->create(['name' => 'United States']);
    Region::factory()->active()->create(['name' => 'European Union']);
    Region::factory()->active()->create(['name' => 'United Kingdom']);

    $response = actingAsSuperAdmin()->getJson(route('admin.regions.search', ['query' => 'king']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('United Kingdom'));
});

test('returns case insensitive search results', function () {
    Region::factory()->active()->create(['name' => 'United States']);
    Region::factory()->active()->create(['name' => 'European Union']);

    $response = actingAsSuperAdmin()->getJson(route('admin.regions.search', ['query' => 'UNITED']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('United States'));
});

test('returns empty results when no regions match query', function () {
    Region::factory()->active()->create(['name' => 'United States']);
    Region::factory()->active()->create(['name' => 'European Union']);

    $response = actingAsSuperAdmin()->getJson(route('admin.regions.search', ['query' => 'nonexistent']));

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('returns only active regions', function () {
    Region::factory()->active()->create(['name' => 'Active Region']);
    Region::factory()->inactive()->create(['name' => 'Inactive Region']);

    $response = actingAsSuperAdmin()->getJson(route('admin.regions.search', ['query' => 'region']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', castAsTranslatableArray('Active Region'));
});

test('returns all active regions when no query parameter provided', function () {
    Region::factory()->active()->create(['name' => 'Region 1']);
    Region::factory()->active()->create(['name' => 'Region 2']);
    Region::factory()->inactive()->create(['name' => 'Region 3']);

    $response = actingAsSuperAdmin()->getJson(route('admin.regions.search'));

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('requires authentication', function () {
    Region::factory()->count(2)->active()->create();

    $response = getJson(route('admin.regions.search'));

    $response->assertUnauthorized();
});

test('requires region reference access', function () {
    Region::factory()->count(2)->active()->create();

    actingAs(userWithPermissions([Permission::RegionsView]))
        ->getJson(route('admin.regions.search'))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    actingAs(userWithPermissions([Permission::DashboardView]))
        ->getJson(route('admin.regions.search'))
        ->assertForbidden();
});
