<?php

declare(strict_types=1);

use App\Actions\UpdateUserAction;
use App\Enums\AdminThemeColor;
use App\Enums\Appearance;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\AppearanceController;
use App\Http\Requests\Admin\UpdateAppearanceRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;

covers(AppearanceController::class, UpdateAppearanceRequest::class, UpdateUserAction::class);

uses()->group('account', 'appearance');

test('can update appearance and theme color', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.appearance.update'), [
        'appearance' => Appearance::Dark->value,
        'admin_theme_color' => AdminThemeColor::Blue->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('users', [
        'id' => auth()->id(),
        'name' => auth()->user()->name,
        'email' => auth()->user()->email,
        'appearance' => Appearance::Dark->value,
        'admin_theme_color' => AdminThemeColor::Blue->value,
    ]);
});

test('can update only appearance', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.appearance.update'), [
        'appearance' => Appearance::Light->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(auth()->user()->appearance->value)->toBe(Appearance::Light->value);
});

test('can update only theme color', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.appearance.update'), [
        'admin_theme_color' => AdminThemeColor::Emerald->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(auth()->user()->admin_theme_color->value)->toBe(AdminThemeColor::Emerald->value);
});

test('validates appearance must be valid enum value', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.appearance.update'), [
        'appearance' => 'invalid_appearance',
        'admin_theme_color' => AdminThemeColor::Red->value,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['appearance']);
});

test('validates theme color must be valid enum value', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.appearance.update'), [
        'appearance' => Appearance::System->value,
        'admin_theme_color' => 'invalid_theme',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['admin_theme_color']);
});

test('can update with all valid appearance values', function (Appearance $appearance) {
    $response = actingAsSuperAdmin()->patch(route('admin.appearance.update'), [
        'appearance' => $appearance->value,
        'admin_theme_color' => AdminThemeColor::Neutral->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(auth()->user()->appearance->value)->toBe($appearance->value);
})->with(Appearance::cases());

test('can update with all valid theme color values', function (AdminThemeColor $adminThemeColor) {
    $response = actingAsSuperAdmin()->patch(route('admin.appearance.update'), [
        'appearance' => Appearance::System->value,
        'admin_theme_color' => $adminThemeColor->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(auth()->user()->admin_theme_color->value)->toBe($adminThemeColor->value);
})->with(AdminThemeColor::cases());

test('requires authentication', function () {
    $response = patch(route('admin.appearance.update'), [
        'appearance' => Appearance::Dark->value,
        'admin_theme_color' => AdminThemeColor::Blue->value,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('appearance updates do not require any permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $adminUser = actingAsAdmin();

    $response = $adminUser->patch(route('admin.appearance.update'), [
        'appearance' => Appearance::Light->value,
        'admin_theme_color' => AdminThemeColor::Emerald->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(auth()->user()->appearance->value)->toBe(Appearance::Light->value);
    expect(auth()->user()->admin_theme_color->value)->toBe(AdminThemeColor::Emerald->value);

    $role->revokePermissionTo(Permission::SettingsGeneralConfigure);

    $response = $adminUser->patch(route('admin.appearance.update'), [
        'appearance' => Appearance::Dark->value,
        'admin_theme_color' => AdminThemeColor::Red->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    // Appearance should still update without any permission
    expect(auth()->user()->appearance->value)->toBe(Appearance::Dark->value);
    expect(auth()->user()->admin_theme_color->value)->toBe(AdminThemeColor::Red->value);
});
