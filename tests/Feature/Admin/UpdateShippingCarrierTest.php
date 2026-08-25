<?php

declare(strict_types=1);

use App\Actions\UpdateShippingCarrierAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\ShippingCarrierDriver;
use App\Http\Controllers\Admin\ShippingCarrierController;
use App\Http\Requests\Admin\UpdateShippingCarrierRequest;
use App\Models\ShippingCarrier;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\patch;

covers(ShippingCarrierController::class, UpdateShippingCarrierRequest::class, UpdateShippingCarrierAction::class);

uses()->group('shipping');

test('updates a shipping carrier', function () {
    $carrier = ShippingCarrier::factory()->create([
        'name' => 'Old Carrier',
        'driver' => ShippingCarrierDriver::Manual,
    ]);

    $data = [
        'name' => 'Updated Carrier',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.carriers.update', $carrier), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_carriers', [
        'id' => $carrier->id,
        'name' => castAsTranslatableJson('Updated Carrier'),
    ]);
});

test('validates required fields', function () {
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.carriers.update', $carrier), [
        'name' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name']);
});

test('requires authentication', function () {
    $carrier = ShippingCarrier::factory()->create();

    $response = patch(route('admin.shipping.carriers.update', $carrier), [
        'name' => 'Updated Carrier',
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires shipping.carriers.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $carrier = ShippingCarrier::factory()->create(['driver' => ShippingCarrierDriver::Manual->value]);

    $response = actingAsAdmin()->patch(route('admin.shipping.carriers.update', $carrier), [
        'name' => 'Updated Carrier',
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_carriers', [
        'id' => $carrier->id,
        'name' => castAsTranslatableJson('Updated Carrier'),
    ]);

    $role->revokePermissionTo(Permission::SettingsShippingConfigure);

    $anotherCarrier = ShippingCarrier::factory()->create(['driver' => ShippingCarrierDriver::Manual->value]);

    $response = actingAsAdmin()->patch(route('admin.shipping.carriers.update', $anotherCarrier), [
        'name' => 'Forbidden Update',
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('shipping_carriers', [
        'name' => castAsTranslatableJson('Forbidden Update'),
    ]);
});
