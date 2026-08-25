<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\SettingController;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(SettingController::class);

uses()->group('setting');

test('displays setting list page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/list')
        );
});

test('requires authentication', function () {
    $response = get(route('admin.settings.index'));

    $response->assertRedirect(route('admin.login'));
});

test('grants access with any single settings permission', function () {
    $user = userWithPermissions([Permission::SettingsMailConfigure]);

    $response = actingAs($user)->get(route('admin.settings.index'));

    $response->assertOk();
});

test('forbids access without any settings permission', function () {
    $user = userWithPermissions([Permission::DashboardView]);

    $response = actingAs($user)->get(route('admin.settings.index'));

    $response->assertForbidden();
});
