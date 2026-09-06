<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontCartController;
use App\Http\Requests\Admin\UpdateStorefrontCartRequest;
use App\Models\Setting;
use App\Queries\CartSettingsQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers([
    StorefrontCartController::class,
    UpdateStorefrontCartRequest::class,
    UpdateSettingsAction::class,
    CartSettingsQuery::class,
]);

uses()->group('storefront', 'cart');

test('displays cart settings page with current settings', function () {
    Setting::setValue('storefront_cart_show_cross_sells', false);

    actingAsSuperAdmin()
        ->get(route('admin.storefront.cart.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/storefront/cart')
            ->where('settings.show_cross_sells', false));
});

test('displays default settings when no settings exist', function () {
    Setting::query()->where('key', 'storefront_cart_show_cross_sells')->delete();

    actingAsSuperAdmin()
        ->get(route('admin.storefront.cart.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('settings.show_cross_sells', true));
});

test('cross-sell visibility can be toggled', function () {
    actingAsSuperAdmin()
        ->patch(route('admin.storefront.cart.update'), ['storefront_cart_show_cross_sells' => false])
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', ['key' => 'storefront_cart_show_cross_sells', 'value' => '0']);
});

test('validates that the cross-sell setting is a boolean', function () {
    actingAsSuperAdmin()
        ->patch(route('admin.storefront.cart.update'), ['storefront_cart_show_cross_sells' => 'yes please'])
        ->assertSessionHasErrors('storefront_cart_show_cross_sells');
});

test('requires authentication for edit', function () {
    get(route('admin.storefront.cart.edit'))->assertRedirect(route('admin.login'));
});

test('requires authentication for update', function () {
    patch(route('admin.storefront.cart.update'), ['storefront_cart_show_cross_sells' => false])
        ->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission for edit', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    actingAsAdmin()->get(route('admin.storefront.cart.edit'))->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    actingAsAdmin()->get(route('admin.storefront.cart.edit'))->assertForbidden();
});

test('requires storefront.update permission for update', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    actingAsAdmin()
        ->patch(route('admin.storefront.cart.update'), ['storefront_cart_show_cross_sells' => false])
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    actingAsAdmin()
        ->patch(route('admin.storefront.cart.update'), ['storefront_cart_show_cross_sells' => true])
        ->assertForbidden();
});
