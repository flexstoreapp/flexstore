<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontFooterController;
use App\Models\Setting;
use App\Queries\AdminFooterMenuItemListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(StorefrontFooterController::class, AdminFooterMenuItemListQuery::class);

uses()->group('storefront');

test('displays footer settings', function () {
    Setting::setValue('storefront_footer_show_social_links', true);
    Setting::setValue('storefront_footer_show_payment_badges', false);
    Setting::setValue('storefront_footer_payment_method_preset', 'credit_cards');
    Setting::setValue('storefront_footer_payment_methods', ['visa', 'mastercard']);
    Setting::setValue('storefront_footer_copyright_text', 'Custom copyright');

    $response = actingAsSuperAdmin()->get(route('admin.storefront.footer.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/footer')
                ->has('settings')
                ->where('settings.show_social_links', true)
                ->where('settings.show_payment_badges', false)
                ->where('settings.payment_method_preset', 'credit_cards')
                ->where('settings.payment_methods', ['visa', 'mastercard'])
                ->where('settings.copyright_text', 'Custom copyright')
        );
});

test('displays default footer settings', function () {
    $response = actingAsSuperAdmin()->get(route('admin.storefront.footer.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/footer')
                ->has('settings')
                ->where('settings.show_social_links', true)
                ->where('settings.show_payment_badges', true)
                ->where('settings.payment_method_preset', 'all')
                ->where('settings.payment_methods', ['visa', 'mastercard', 'amex', 'paypal', 'apple_pay', 'google_pay'])
        );
});

test('requires authentication', function () {
    $response = get(route('admin.storefront.footer.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.footer.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.footer.edit'));

    $response->assertForbidden();
});
