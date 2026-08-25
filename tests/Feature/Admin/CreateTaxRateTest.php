<?php

declare(strict_types=1);

use App\Actions\StoreTaxRateAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TaxCategory;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Requests\Admin\StoreTaxRateRequest;
use App\Models\Region;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

covers(TaxRateController::class, StoreTaxRateRequest::class, StoreTaxRateAction::class);

uses()->group('tax');

test('creates a tax rate with all fields', function () {
    $region = Region::factory()->create();
    $taxCategory = TaxCategory::Standard->value;

    $createData = [
        'name' => 'New Tax Rate',
        'tax_category' => $taxCategory,
        'region_id' => $region->id,
        'rate' => '8.2500',
        'min_order_value' => '10.0000',
        'max_order_value' => '500.0000',
        'is_compound' => false,
        'is_active' => true,
        'priority' => 10,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.tax.rates.store'), $createData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('tax_rates', [
        'name' => castAsTranslatableJson('New Tax Rate'),
        'tax_category' => $taxCategory,
        'region_id' => $region->id,
        'rate' => '8.2500',
        'min_order_value' => '10.0000',
        'max_order_value' => '500.0000',
        'is_compound' => false,
        'is_active' => true,
        'priority' => 10,
    ]);
});

test('creates a tax rate with minimal required fields', function () {
    $region = Region::factory()->create();

    $createData = [
        'name' => 'Basic Tax',
        'region_id' => $region->id,
        'rate' => '5.0000',
        'is_compound' => false,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.tax.rates.store'), $createData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('tax_rates', [
        'name' => castAsTranslatableJson('Basic Tax'),
        'tax_category' => null,
        'region_id' => $region->id,
        'rate' => '5.0000',
        'min_order_value' => null,
        'max_order_value' => null,
        'is_compound' => false,
        'is_active' => true,
    ]);
});

test('creates a tax rate for all categories when tax_category is empty', function () {
    $region = Region::factory()->create();

    $createData = [
        'name' => 'General Tax',
        'tax_category' => '', // empty string should be converted to null
        'region_id' => $region->id,
        'rate' => '7.5000',
        'is_compound' => false,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.tax.rates.store'), $createData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('tax_rates', [
        'name' => castAsTranslatableJson('General Tax'),
        'tax_category' => null,
        'region_id' => $region->id,
        'rate' => '7.5000',
        'is_compound' => false,
        'is_active' => true,
    ]);
});

test('validates required fields when creating', function () {

    $response = actingAsSuperAdmin()->post(route('admin.tax.rates.store'), [
        'name' => '',
        'region_id' => '',
        'rate' => '',
        'is_active' => '',
        'is_compound' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'region_id', 'rate', 'is_active', 'is_compound']);
});

test('validates numeric fields when creating', function () {
    $region = Region::factory()->create();

    $createData = [
        'name' => 'Test Tax',
        'region_id' => $region->id,
        'rate' => 'not-a-number',
        'min_order_value' => 'not-a-number',
        'max_order_value' => 'not-a-number',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.tax.rates.store'), $createData);

    $response->assertRedirectBack()
        ->assertInvalid(['rate', 'min_order_value', 'max_order_value']);
});

test('validates boolean fields when creating', function () {
    $region = Region::factory()->create();

    $createData = [
        'name' => 'Test Tax',
        'region_id' => $region->id,
        'rate' => '5.0000',
        'is_active' => 'not-a-boolean',
        'is_compound' => 'not-a-boolean',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.tax.rates.store'), $createData);

    $response->assertRedirectBack()
        ->assertInvalid(['is_active', 'is_compound']);
});

test('validates foreign key constraints when creating', function () {
    $createData = [
        'name' => 'Test Tax',
        'region_id' => 99999, // non-existent region
        'tax_category' => 'invalid-category',
        'rate' => '5.0000',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.tax.rates.store'), $createData);

    $response->assertRedirectBack()
        ->assertInvalid(['region_id', 'tax_category']);
});

test('requires authentication', function () {
    $region = Region::factory()->create();

    $response = post(route('admin.tax.rates.store'), [
        'name' => 'Test Tax Rate',
        'region_id' => $region->id,
        'rate' => '8.2500',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires tax.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $region = Region::factory()->create();

    $response = actingAsAdmin()->post(route('admin.tax.rates.store'), [
        'name' => 'Test Tax Rate',
        'region_id' => $region->id,
        'rate' => '8.2500',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('tax_rates', [
        'name' => castAsTranslatableJson('Test Tax Rate'),
    ]);

    $role->revokePermissionTo(Permission::SettingsTaxConfigure);

    $response = actingAsAdmin()->post(route('admin.tax.rates.store'), [
        'name' => 'Another Tax Rate',
        'region_id' => $region->id,
        'rate' => '10.0000',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('tax_rates', [
        'name' => castAsTranslatableJson('Another Tax Rate'),
    ]);
});
