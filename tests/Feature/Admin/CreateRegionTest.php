<?php

declare(strict_types=1);

use App\Actions\StoreRegionAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Requests\Admin\StoreRegionRequest;
use App\Rules\ValidPostalCodePattern;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\castAsJson;
use function Pest\Laravel\post;

covers(RegionController::class, StoreRegionRequest::class, StoreRegionAction::class, ValidPostalCodePattern::class);

uses()->group('region');

test('creates a new region', function () {
    $data = [
        'name' => 'Europe',
        'countries' => ['DE', 'FR'],
        'states' => ['Bavaria', 'Normandy'],
        'postal_codes' => ['80331', '75001'],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('regions', [
        'name' => castAsTranslatableJson('Europe'),
        'countries' => castAsJson(['DE', 'FR']),
        'states' => castAsJson(['Bavaria', 'Normandy']),
        'postal_codes' => castAsJson(['80331', '75001']),
        'is_active' => true,
    ]);
});

test('validates required fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => '',
        'countries' => [],
        'is_active' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'countries', 'is_active']);
});

test('validates name field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => str_repeat('a', 256), // Exceeds max:255
        'countries' => ['US'],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates countries field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => 'North America',
        'countries' => ['INVALID'], // Invalid country code
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['countries', 'countries.0']);

    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => 'North America',
        'countries' => [], // Empty array
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('countries');
});

test('validates states field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => 'North America',
        'countries' => ['US'],
        'states' => [str_repeat('a', 256)], // Exceeds max:255
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('states.0');
});

test('validates postal_codes field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => 'North America',
        'countries' => ['US'],
        'postal_codes' => [str_repeat('a', 256)], // Exceeds max:255
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('postal_codes.0');
});

test('accepts exact, wildcard, range, and hyphenated literal postal_code patterns', function () {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => 'Mixed patterns',
        'countries' => ['US'],
        'postal_codes' => ['90210', '902*', '60601..60699', '100-0001'],
        'is_active' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('regions', [
        'name' => castAsTranslatableJson('Mixed patterns'),
        'postal_codes' => castAsJson(['90210', '902*', '60601..60699', '100-0001']),
    ]);
});

test('rejects invalid postal_code patterns', function (string $pattern) {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => 'North America',
        'countries' => ['US'],
        'postal_codes' => [$pattern],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('postal_codes.0');
})->with([
    'reversed range' => ['90299..90210'],
    'malformed range' => ['90210..'],
    'bare wildcard' => ['*'],
    'misplaced wildcard' => ['9*0'],
    'illegal characters' => ['90210!'],
]);

test('validates is_active must be boolean', function () {
    $response = actingAsSuperAdmin()->post(route('admin.regions.store'), [
        'name' => 'North America',
        'countries' => ['US'],
        'is_active' => 'not-a-boolean', // Invalid boolean
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('is_active');
});

test('requires authentication', function () {
    $response = post(route('admin.regions.store'), [
        'name' => 'Test Region',
        'countries' => ['US'],
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires regions.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->post(route('admin.regions.store'), [
        'name' => 'Test Region',
        'countries' => ['US'],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('regions', [
        'name' => castAsTranslatableJson('Test Region'),
    ]);

    $role->revokePermissionTo(Permission::RegionsManage);

    $response = actingAsAdmin()->post(route('admin.regions.store'), [
        'name' => 'Another Region',
        'countries' => ['CA'],
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('regions', [
        'name' => castAsTranslatableJson('Another Region'),
    ]);
});
