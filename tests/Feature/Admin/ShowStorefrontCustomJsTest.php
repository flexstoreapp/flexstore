<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontCustomJsController;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(StorefrontCustomJsController::class);

uses()->group('storefront');

test('displays custom js page with current js', function () {
    $customJs = "console.log('Hello World');";
    Setting::setValue('storefront_custom_js', $customJs);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.custom-js.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/custom-js')
                ->where('customJs', $customJs)
        );
});

test('displays empty js when no setting exists', function () {
    Setting::query()->where('key', 'storefront_custom_js')->delete();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.custom-js.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/custom-js')
                ->where('customJs', '')
        );
});

test('requires authentication', function () {
    $response = get(route('admin.storefront.custom-js.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.custom-js.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.custom-js.edit'));

    $response->assertForbidden();
});
