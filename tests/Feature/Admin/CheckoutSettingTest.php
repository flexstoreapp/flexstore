<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CheckoutSettingController;
use App\Http\Requests\Admin\UpdateCheckoutSettingRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(CheckoutSettingController::class, UpdateCheckoutSettingRequest::class);

uses()->group('setting', 'checkout');

test('requires authentication', function () {
    $response = get(route('admin.settings.checkout.show'));

    $response->assertRedirect(route('admin.login'));
});

test('rejects an out of range reservation window', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.checkout.update'), [
        'checkout_reservation_minutes' => 0,
    ]);

    $response->assertSessionHasErrors('checkout_reservation_minutes');
});

test('requires settings.checkout.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.checkout.show'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::SettingsCheckoutConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.checkout.show'));

    $response->assertForbidden();
});

test('requires settings.checkout.configure permission to update', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $role->revokePermissionTo(Permission::SettingsCheckoutConfigure);

    $response = actingAsAdmin()->patch(route('admin.settings.checkout.update'), [
        'guest_checkout_enabled' => false,
    ]);

    $response->assertForbidden();
});
