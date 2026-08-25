<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontThemeController;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(StorefrontThemeController::class);

uses()->group('storefront', 'theme');

test('displays theme settings page with current accent', function () {
    Setting::setValue('storefront_theme_color', 'violet');

    $response = actingAsSuperAdmin()->get(route('admin.storefront.theme.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/theme')
                ->where('storefrontThemeColor', 'violet')
        );
});

test('displays default accent when no setting exists', function () {
    Setting::query()->where('key', 'storefront_theme_color')->delete();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.theme.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/theme')
                ->where('storefrontThemeColor', 'blue')
        );
});

test('displays all theme options correctly', function () {
    $response = actingAsSuperAdmin()->get(route('admin.storefront.theme.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/theme')
                ->has('storefrontThemeColor')
        );
});

test('requires authentication', function () {
    $response = get(route('admin.storefront.theme.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.theme.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.theme.edit'));

    $response->assertForbidden();
});
