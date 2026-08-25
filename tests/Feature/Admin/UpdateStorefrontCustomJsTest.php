<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontCustomJsController;
use App\Http\Requests\Admin\UpdateStorefrontCustomJsRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

covers(StorefrontCustomJsController::class, UpdateStorefrontCustomJsRequest::class, UpdateSettingsAction::class);

uses()->group('storefront');

test('updates custom js', function () {
    $customJs = "document.addEventListener('DOMContentLoaded', function() { console.log('Loaded'); });";

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => $customJs,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_js',
        'value' => $customJs,
    ]);
});

test('accepts null custom js', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => null,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_js',
        'value' => null,
    ]);
});

test('accepts empty custom js', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => '',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_js',
        'value' => null,
    ]);
});

test('validates max length of 50000 characters', function () {
    $longJs = str_repeat('a', 50001);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => $longJs,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_custom_js');
});

test('validates custom js must be a string', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => ['invalid' => 'array'],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_custom_js');
});

test('requires authentication', function () {
    $response = patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => "console.log('test');",
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $customJs = "console.log('authorized');";

    $response = actingAsAdmin()->patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => $customJs,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_custom_js',
        'value' => $customJs,
    ]);

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.custom-js.update'), [
        'storefront_custom_js' => "console.log('unauthorized');",
    ]);

    $response->assertForbidden();
});
