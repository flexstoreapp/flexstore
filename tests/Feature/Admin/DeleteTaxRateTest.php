<?php

declare(strict_types=1);

use App\Actions\DestroyTaxRateAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\TaxRateController;
use App\Models\TaxRate;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(TaxRateController::class, DestroyTaxRateAction::class);

uses()->group('tax');

test('can delete a tax rate', function () {
    $taxRate = TaxRate::factory()->create();

    $response = actingAsSuperAdmin()->delete(route('admin.tax.rates.destroy', $taxRate));

    $response->assertRedirect();

    assertDatabaseMissing('tax_rates', [
        'id' => $taxRate->id,
    ]);
});

test('requires authentication', function () {
    $taxRate = TaxRate::factory()->create();

    $response = delete(route('admin.tax.rates.destroy', $taxRate));

    $response->assertRedirect(route('admin.login'));
});

test('requires tax.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $taxRate = TaxRate::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.tax.rates.destroy', $taxRate));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('tax_rates', [
        'id' => $taxRate->id,
    ]);

    $role->revokePermissionTo(Permission::SettingsTaxConfigure);

    $anotherTaxRate = TaxRate::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.tax.rates.destroy', $anotherTaxRate));

    $response->assertForbidden();

    assertDatabaseHas('tax_rates', [
        'id' => $anotherTaxRate->id,
    ]);
});
