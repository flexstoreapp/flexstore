<?php

declare(strict_types=1);

use App\Actions\DestroyShippingRateAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Models\ShippingRate;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(ShippingRateController::class, DestroyShippingRateAction::class);

uses()->group('shipping');

test('deletes a shipping rate', function () {
    $shippingRate = ShippingRate::factory()->create();

    actingAsSuperAdmin()->delete(route('admin.shipping.rates.destroy', $shippingRate));

    expect(ShippingRate::find($shippingRate->id))->toBeNull();
});

test('requires authentication', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = delete(route('admin.shipping.rates.destroy', $shippingRate));

    $response->assertRedirect(route('admin.login'));
});

test('requires shipping.rates.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.shipping.rates.destroy', $shippingRate));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('shipping_rates', [
        'id' => $shippingRate->id,
    ]);

    $role->revokePermissionTo(Permission::SettingsShippingConfigure);

    $anotherRate = ShippingRate::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.shipping.rates.destroy', $anotherRate));

    $response->assertForbidden();

    assertDatabaseHas('shipping_rates', [
        'id' => $anotherRate->id,
    ]);
});
