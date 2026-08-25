<?php

declare(strict_types=1);

use App\Actions\UpdateRegionAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Requests\Admin\UpdateRegionRequest;
use App\Models\Region;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\castAsJson;
use function Pest\Laravel\patch;

covers(RegionController::class, UpdateRegionRequest::class, UpdateRegionAction::class);

uses()->group('region');

test('updates a region', function () {
    $region = Region::factory()->create([
        'name' => 'Asia',
        'countries' => ['JP', 'CN'],
        'states' => ['Tokyo', 'Beijing'],
        'postal_codes' => ['100-0001', '100000'],
        'is_active' => true,
    ]);

    $data = [
        'name' => 'East Asia',
        'countries' => ['JP', 'KR'],
        'states' => ['Tokyo', 'Seoul'],
        'postal_codes' => ['100-0001', '04520'],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.regions.update', $region), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('regions', [
        'id' => $region->id,
        'name' => castAsTranslatableJson('East Asia'),
        'countries' => castAsJson(['JP', 'KR']),
        'states' => castAsJson(['Tokyo', 'Seoul']),
        'postal_codes' => castAsJson(['100-0001', '04520']),
        'is_active' => true,
    ]);
});

test('validates name field on update', function () {
    $region = Region::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.regions.update', $region), [
        'name' => str_repeat('a', 256), // Exceeds max:255
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates countries field on update', function () {
    $region = Region::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.regions.update', $region), [
        'countries' => ['INVALID'], // Invalid country code
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['countries', 'countries.0']);

    $response = actingAsSuperAdmin()->patch(route('admin.regions.update', $region), [
        'countries' => [], // Empty array
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('countries');
});

test('validates states field on update', function () {
    $region = Region::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.regions.update', $region), [
        'states' => [str_repeat('a', 256)], // Exceeds max:255
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('states.0');
});

test('validates postal_codes field on update', function () {
    $region = Region::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.regions.update', $region), [
        'postal_codes' => [str_repeat('a', 256)], // Exceeds max:255
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('postal_codes.0');
});

test('validates is_active must be boolean on update', function () {
    $region = Region::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.regions.update', $region), [
        'is_active' => 'not-a-boolean', // Invalid boolean
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('is_active');
});

test('requires authentication', function () {
    $region = Region::factory()->create();

    $response = patch(route('admin.regions.update', $region), [
        'name' => 'Updated Region',
        'countries' => ['US'],
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires regions.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $region = Region::factory()->create(['name' => 'Original Region']);

    $response = actingAsAdmin()->patch(route('admin.regions.update', $region), [
        'name' => 'Updated Region',
        'countries' => ['US'],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('regions', [
        'id' => $region->id,
        'name' => castAsTranslatableJson('Updated Region'),
    ]);

    $role->revokePermissionTo(Permission::RegionsManage);

    $anotherRegion = Region::factory()->create(['name' => 'Another Region']);

    $response = actingAsAdmin()->patch(route('admin.regions.update', $anotherRegion), [
        'name' => 'Forbidden Update',
        'countries' => ['CA'],
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('regions', [
        'name' => castAsTranslatableJson('Forbidden Update'),
    ]);
});
