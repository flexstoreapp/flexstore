<?php

declare(strict_types=1);

use App\Actions\StoreShippingCarrierAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\ShippingCarrierDriver;
use App\Http\Controllers\Admin\ShippingCarrierController;
use App\Http\Requests\Admin\StoreShippingCarrierRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

covers(ShippingCarrierController::class, StoreShippingCarrierRequest::class, StoreShippingCarrierAction::class);

uses()->group('shipping');

test('creates a new shipping carrier', function () {
    $data = [
        'name' => 'Standard Shipping',
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.shipping.carriers.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_carriers', [
        'name' => castAsTranslatableJson('Standard Shipping'),
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ]);
});

test('validates required fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.shipping.carriers.store'), [
        'name' => '',
        'driver' => '',
        'is_active' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'driver', 'is_active']);
});

test('requires authentication', function () {
    $response = post(route('admin.shipping.carriers.store'), [
        'name' => 'Test Carrier',
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires shipping.carriers.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->post(route('admin.shipping.carriers.store'), [
        'name' => 'Test Carrier',
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_carriers', [
        'name' => castAsTranslatableJson('Test Carrier'),
    ]);

    $role->revokePermissionTo(Permission::SettingsShippingConfigure);

    $response = actingAsAdmin()->post(route('admin.shipping.carriers.store'), [
        'name' => 'Another Carrier',
        'driver' => ShippingCarrierDriver::Manual->value,
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('shipping_carriers', [
        'name' => castAsTranslatableJson('Another Carrier'),
    ]);
});
