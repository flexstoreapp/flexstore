<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontFooterController;
use App\Http\Requests\Admin\UpdateStorefrontFooterRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\patch;

covers(StorefrontFooterController::class, UpdateStorefrontFooterRequest::class, UpdateSettingsAction::class);

uses()->group('storefront');

test('updates footer show social links setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_show_social_links' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_footer_show_social_links',
        'value' => '0',
    ]);
});

test('updates footer show payment badges setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_show_payment_badges' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_footer_show_payment_badges',
        'value' => '0',
    ]);
});

test('updates footer show copyright setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_show_copyright' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_footer_show_copyright',
        'value' => '0',
    ]);
});

test('updates footer copyright text', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_copyright_text' => 'All rights reserved.',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_footer_copyright_text',
        'value' => 'All rights reserved.',
    ]);
});

test('the powered by attribution cannot be turned off', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_show_powered_by' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('settings', [
        'key' => 'storefront_footer_show_powered_by',
        'value' => '0',
    ]);
});

test('updates footer payment method preset', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_payment_method_preset' => 'credit_cards',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_footer_payment_method_preset',
        'value' => 'credit_cards',
    ]);
});

test('updates footer payment methods', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_payment_methods' => ['visa', 'discover', 'jcb', 'unionpay', 'mada', 'upi', 'ideal'],
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_footer_payment_methods',
        'value' => json_encode(['visa', 'discover', 'jcb', 'unionpay', 'mada', 'upi', 'ideal']),
    ]);
});

test('validates payment method preset enum', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_payment_method_preset' => 'invalid_preset',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_footer_payment_method_preset');
});

test('validates payment methods array contains valid methods', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_payment_methods' => ['visa', 'invalid_method'],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_footer_payment_methods.1');
});

test('validates footer copyright text max length', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_copyright_text' => str_repeat('a', 501),
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_footer_copyright_text');
});

test('requires authentication', function () {
    $response = patch(route('admin.storefront.footer.update'), [
        'storefront_footer_show_social_links' => false,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_show_social_links' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_footer_show_social_links',
        'value' => '0',
    ]);

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.footer.update'), [
        'storefront_footer_show_social_links' => true,
    ]);

    $response->assertForbidden();
});
