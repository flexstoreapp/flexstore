<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontCustomCssController;
use App\Http\Requests\Admin\UpdateStorefrontCustomCssRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

covers(StorefrontCustomCssController::class, UpdateStorefrontCustomCssRequest::class, UpdateSettingsAction::class);

uses()->group('storefront');

test('updates custom css', function () {
    $customCss = '.custom-class { background-color: blue; font-size: 16px; }';

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => $customCss,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_css',
        'value' => $customCss,
    ]);
});

test('accepts null custom css', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => null,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_css',
        'value' => null,
    ]);
});

test('accepts empty custom css', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => '',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_css',
        'value' => null,
    ]);
});

test('validates max length of 50000 characters', function () {
    $longCss = str_repeat('a', 50001);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => $longCss,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_custom_css');
});

test('validates custom css must be a string', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => ['invalid' => 'array'],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_custom_css');
});

test('requires authentication', function () {
    $response = patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => '.test { color: red; }',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $customCss = '.authorized { color: green; }';

    $response = actingAsAdmin()->patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => $customCss,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_css',
        'value' => $customCss,
    ]);

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.custom-css.update'), [
        'storefront_custom_css' => '.unauthorized { color: red; }',
    ]);

    $response->assertForbidden();
});
