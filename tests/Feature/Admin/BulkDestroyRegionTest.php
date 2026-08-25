<?php

declare(strict_types=1);

use App\Actions\BulkDestroyRegionAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BulkRegionController;
use App\Http\Requests\Admin\BulkDestroyRegionRequest;
use App\Models\Region;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(BulkRegionController::class, BulkDestroyRegionRequest::class, BulkDestroyRegionAction::class);

uses()->group('region');

test('bulk deletes regions', function () {
    $regions = Region::factory(2)->create();
    $ids = $regions->pluck('id')->all();

    $response = actingAsSuperAdmin()->delete(route('admin.regions.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    foreach ($ids as $id) {
        assertDatabaseMissing('regions', ['id' => $id]);
    }
});

test('requires at least one region id', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.regions.bulk.destroy'), [
        'ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('validates that ids exists', function () {
    $region = Region::factory()->create();
    $nonExistentId = 9999;

    $response = actingAsSuperAdmin()->delete(route('admin.regions.bulk.destroy'), [
        'ids' => [$region->id, $nonExistentId],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('requires authentication', function () {
    $regions = Region::factory()->count(2)->create();

    $response = delete(route('admin.regions.bulk.destroy'), [
        'ids' => $regions->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires regions.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $regions = Region::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.regions.bulk.destroy'), [
        'ids' => $regions->pluck('id')->all(),
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($regions as $region) {
        assertDatabaseMissing('regions', [
            'id' => $region->id,
        ]);
    }

    $role->revokePermissionTo(Permission::RegionsDelete);

    $moreRegions = Region::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.regions.bulk.destroy'), [
        'ids' => $moreRegions->pluck('id')->all(),
    ]);

    $response->assertForbidden();

    foreach ($moreRegions as $region) {
        assertDatabaseHas('regions', [
            'id' => $region->id,
        ]);
    }
});
