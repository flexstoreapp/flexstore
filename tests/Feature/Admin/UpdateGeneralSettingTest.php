<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Requests\Admin\UpdateGeneralSettingRequest;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(GeneralSettingController::class, UpdateGeneralSettingRequest::class, UpdateSettingsAction::class);

uses()->group('setting');

test('displays general settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.general.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/general')
                ->has('settings')
        );
});

test('can update general settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.general.update'), [
        'default_low_stock_threshold' => 15,
        'auto_approve_reviews' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'default_low_stock_threshold',
        'value' => 15,
    ]);

    assertDatabaseHas('settings', [
        'key' => 'auto_approve_reviews',
        'value' => true,
    ]);
});

test('cannot update general settings with invalid data', function () {
    $response = actingAsSuperAdmin()
        ->patch(route('admin.settings.general.update'), [
            'default_low_stock_threshold' => -1,
            'auto_approve_reviews' => 'invalid',
        ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'default_low_stock_threshold',
            'auto_approve_reviews',
        ]);
});

test('requires authentication', function () {
    $response = get(route('admin.settings.general.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.general.update'), [
        'default_low_stock_threshold' => 10,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.general.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.general.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.general.update'), [
        'default_low_stock_threshold' => 20,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'default_low_stock_threshold',
        'value' => 20,
    ]);

    $role->revokePermissionTo(Permission::SettingsGeneralConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.general.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.general.update'), [
        'default_low_stock_threshold' => 30,
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', [
        'key' => 'default_low_stock_threshold',
        'value' => 20,
    ]);
});
