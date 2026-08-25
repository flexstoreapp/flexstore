<?php

declare(strict_types=1);

use App\Actions\DestroyShippingCarrierAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ShippingCarrierController;
use App\Models\ShippingCarrier;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(ShippingCarrierController::class, DestroyShippingCarrierAction::class);

uses()->group('shipping');

test('deletes a shipping carrier', function () {
    $carrier = ShippingCarrier::factory()->create();

    actingAsSuperAdmin()->delete(route('admin.shipping.carriers.destroy', $carrier));

    expect(ShippingCarrier::find($carrier->id))->toBeNull();
});

test('returns 404 when trying to delete non-existent shipping carrier', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.shipping.carriers.destroy', 999));

    $response->assertNotFound();
});

test('requires authentication', function () {
    $carrier = ShippingCarrier::factory()->create();

    $response = delete(route('admin.shipping.carriers.destroy', $carrier));

    $response->assertRedirect(route('admin.login'));
});

test('requires shipping.carriers.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.shipping.carriers.destroy', $carrier));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('shipping_carriers', [
        'id' => $carrier->id,
    ]);

    $role->revokePermissionTo(Permission::SettingsShippingConfigure);

    $anotherCarrier = ShippingCarrier::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.shipping.carriers.destroy', $anotherCarrier));

    $response->assertForbidden();

    assertDatabaseHas('shipping_carriers', [
        'id' => $anotherCarrier->id,
    ]);
});
