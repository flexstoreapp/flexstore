<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\StorefrontAccent;
use App\Http\Controllers\Admin\StorefrontThemeController;
use App\Http\Requests\Admin\UpdateStorefrontThemeRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

covers(StorefrontThemeController::class, UpdateStorefrontThemeRequest::class, UpdateSettingsAction::class);

uses()->group('storefront');

test('updates storefront theme color', function (StorefrontAccent $accent) {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.theme.update'), [
        'storefront_theme_color' => $accent->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_theme_color',
        'value' => $accent->value,
    ]);
})->with(StorefrontAccent::cases());

test('rejects an empty storefront_theme_color', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.theme.update'), [
        'storefront_theme_color' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_theme_color');
});

test('leaves the theme color unchanged when the field is omitted', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.theme.update'), []);

    $response->assertRedirectBack()
        ->assertValid('storefront_theme_color');
});

test('validates storefront_theme_color is valid', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.theme.update'), [
        'storefront_theme_color' => 'invalid_theme',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_theme_color');
});

test('requires authentication for update', function () {
    $response = patch(route('admin.storefront.theme.update'), [
        'storefront_theme_color' => 'neutral',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.update permission for update', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.theme.update'), [
        'storefront_theme_color' => 'blue',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_theme_color',
        'value' => 'blue',
    ]);

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.theme.update'), [
        'storefront_theme_color' => 'red',
    ]);

    $response->assertForbidden();
});
