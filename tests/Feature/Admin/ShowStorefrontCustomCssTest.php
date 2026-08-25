<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontCustomCssController;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(StorefrontCustomCssController::class);

uses()->group('storefront');

test('displays custom css page with current css', function () {
    $customCss = '.my-class { color: red; }';
    Setting::setValue('storefront_custom_css', $customCss);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.custom-css.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/custom-css')
                ->where('customCss', $customCss)
        );
});

test('displays empty css when no setting exists', function () {
    Setting::query()->where('key', 'storefront_custom_css')->delete();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.custom-css.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/custom-css')
                ->where('customCss', '')
        );
});

test('requires authentication', function () {
    $response = get(route('admin.storefront.custom-css.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.custom-css.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.custom-css.edit'));

    $response->assertForbidden();
});
