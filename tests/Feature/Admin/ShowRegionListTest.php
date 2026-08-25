<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Requests\Admin\IndexRegionRequest;
use App\Models\Region;
use App\Queries\RegionListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(RegionController::class, RegionListQuery::class, IndexRegionRequest::class);

uses()->group('region');

test('displays region settings page', function () {
    Region::factory(2)->active()->create();

    $response = actingAsSuperAdmin()->get(route('admin.regions.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/regions/list')
                ->has('regions.data', 2)
                ->where('filters.query', null)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('sorts regions by name ascending', function () {
    $regionC = Region::factory()->active()->create(['name' => 'Region C']);
    $regionA = Region::factory()->active()->create(['name' => 'Region A']);
    $regionB = Region::factory()->active()->create(['name' => 'Region B']);

    $response = actingAsSuperAdmin()->get(route('admin.regions.index', ['sort' => 'name', 'direction' => 'asc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/regions/list')
                ->where('regions.data.0.id', $regionA->id)
                ->where('regions.data.1.id', $regionB->id)
                ->where('regions.data.2.id', $regionC->id)
        );
});

test('sorts regions by name descending', function () {
    $regionC = Region::factory()->active()->create(['name' => 'Region C']);
    $regionA = Region::factory()->active()->create(['name' => 'Region A']);
    $regionB = Region::factory()->active()->create(['name' => 'Region B']);

    $response = actingAsSuperAdmin()->get(route('admin.regions.index', ['sort' => 'name', 'direction' => 'desc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/regions/list')
                ->where('regions.data.0.id', $regionC->id)
                ->where('regions.data.1.id', $regionB->id)
                ->where('regions.data.2.id', $regionA->id)
        );
});

test('requires authentication', function () {
    Region::factory(2)->active()->create();

    $response = get(route('admin.regions.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires regions.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    Region::factory(2)->active()->create();

    $response = actingAsAdmin()->get(route('admin.regions.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::RegionsView);

    $response = actingAsAdmin()->get(route('admin.regions.index'));

    $response->assertForbidden();
});
