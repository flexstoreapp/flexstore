<?php

declare(strict_types=1);

use App\Actions\UpdateTaxRateAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TaxCategory;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Requests\Admin\UpdateTaxRateRequest;
use App\Models\Region;
use App\Models\TaxRate;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\patch;

covers(TaxRateController::class, UpdateTaxRateRequest::class, UpdateTaxRateAction::class);

uses()->group('tax');

test('updates a tax rate with all fields', function () {
    $taxRate = TaxRate::factory()->create();
    $region = Region::factory()->create();
    $taxCategory = TaxCategory::Standard->value;

    $updateData = [
        'name' => 'Updated Tax Rate',
        'tax_category' => $taxCategory,
        'region_id' => $region->id,
        'rate' => '10.5000',
        'min_order_value' => '5.0000',
        'max_order_value' => '100.0000',
        'is_compound' => true,
        'is_active' => true,
        'priority' => 5,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.tax.rates.update', $taxRate), $updateData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('tax_rates', [
        'id' => $taxRate->id,
        'name' => castAsTranslatableJson('Updated Tax Rate'),
        'tax_category' => $taxCategory,
        'region_id' => $region->id,
        'rate' => '10.5000',
        'min_order_value' => '5.0000',
        'max_order_value' => '100.0000',
        'is_compound' => true,
        'is_active' => true,
        'priority' => 5,
    ]);
});

test('updates only specified fields', function () {
    $taxRate = TaxRate::factory()->create([
        'name' => 'Original Name',
        'rate' => '5.0000',
    ]);

    $updateData = [
        'rate' => '10.0000',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.tax.rates.update', $taxRate), $updateData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('tax_rates', [
        'id' => $taxRate->id,
        'name' => castAsTranslatableJson('Original Name'), // unchanged
        'rate' => '10.0000', // updated
    ]);
});

test('validates required fields', function () {
    $taxRate = TaxRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.tax.rates.update', $taxRate), [
        'name' => '',
        'region_id' => '',
        'rate' => '',
        'is_compound' => '',
        'is_active' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'region_id', 'rate', 'is_compound', 'is_active']);
});

test('validates numeric fields', function () {
    $taxRate = TaxRate::factory()->create();

    $taxRateData = [
        'rate' => 'not-a-number',
        'min_order_value' => 'not-a-number',
        'max_order_value' => 'not-a-number',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.tax.rates.update', $taxRate), $taxRateData);

    $response->assertRedirectBack()
        ->assertInvalid(['rate', 'min_order_value', 'max_order_value']);
});

test('validates boolean fields', function () {
    $taxRate = TaxRate::factory()->create();

    $taxRateData = [
        'is_active' => 'not-a-boolean',
        'is_compound' => 'not-a-boolean',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.tax.rates.update', $taxRate), $taxRateData);

    $response->assertRedirectBack()
        ->assertInvalid(['is_active', 'is_compound']);
});

test('validates foreign key constraints', function () {
    $taxRate = TaxRate::factory()->create();

    $taxRateData = [
        'region_id' => 99999, // non-existent region
        'tax_category' => 'invalid-category',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.tax.rates.update', $taxRate), $taxRateData);

    $response->assertRedirectBack()
        ->assertInvalid(['region_id', 'tax_category']);
});

test('requires authentication', function () {
    $taxRate = TaxRate::factory()->create();

    $response = patch(route('admin.tax.rates.update', $taxRate), [
        'name' => 'Updated Tax Rate',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires tax.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $region = Region::factory()->create();
    $taxRate = TaxRate::factory()->create(['region_id' => $region->id]);

    $response = actingAsAdmin()->patch(route('admin.tax.rates.update', $taxRate), [
        'name' => 'Updated Tax Rate',
        'region_id' => $region->id,
        'rate' => '8.0000',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('tax_rates', [
        'id' => $taxRate->id,
        'name' => castAsTranslatableJson('Updated Tax Rate'),
    ]);

    $role->revokePermissionTo(Permission::SettingsTaxConfigure);

    $anotherTaxRate = TaxRate::factory()->create(['region_id' => $region->id]);

    $response = actingAsAdmin()->patch(route('admin.tax.rates.update', $anotherTaxRate), [
        'name' => 'Forbidden Update',
        'region_id' => $region->id,
        'rate' => '10.0000',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('tax_rates', [
        'name' => castAsTranslatableJson('Forbidden Update'),
    ]);
});
