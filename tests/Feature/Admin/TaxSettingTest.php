<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\DisplayTaxTotals;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TaxBasedOn;
use App\Http\Controllers\Admin\TaxSettingController;
use App\Http\Requests\Admin\IndexTaxRateRequest;
use App\Http\Requests\Admin\UpdateTaxSettingRequest;
use App\Models\TaxRate;
use App\Queries\TaxRateListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(TaxSettingController::class, UpdateTaxSettingRequest::class, IndexTaxRateRequest::class, UpdateSettingsAction::class, TaxRateListQuery::class);

uses()->group('setting', 'tax');

test('displays tax settings page', function () {
    TaxRate::factory(2)->create();

    $response = actingAsSuperAdmin()->get(route('admin.settings.tax.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/tax')
                ->has('taxRates.data', 2)
                ->has('filters')
        );
});

test('can update tax settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.tax.update'), [
        'prices_include_tax' => false,
        'default_tax_rate' => '0.0000',
        'tax_based_on' => TaxBasedOn::Shipping->value,
        'display_tax_totals' => DisplayTaxTotals::Itemized->value,
        'shipping_is_taxable' => false,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'default_tax_rate',
        'value' => '0.0000',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'tax_based_on',
        'value' => TaxBasedOn::Shipping->value,
    ]);

    assertDatabaseHas('settings', [
        'key' => 'prices_include_tax',
        'value' => false,
    ]);

    assertDatabaseHas('settings', [
        'key' => 'shipping_is_taxable',
        'value' => false,
    ]);

    assertDatabaseHas('settings', [
        'key' => 'display_tax_totals',
        'value' => DisplayTaxTotals::Itemized->value,
    ]);
});
test('requires authentication', function () {
    $response = get(route('admin.settings.tax.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.tax.update'), [
        'prices_include_tax' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.tax.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.tax.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.tax.update'), [
        'prices_include_tax' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'prices_include_tax',
        'value' => '1',
    ]);

    $role->revokePermissionTo(Permission::SettingsTaxConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.tax.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.tax.update'), [
        'prices_include_tax' => false,
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', [
        'key' => 'prices_include_tax',
        'value' => '1',
    ]);
});
