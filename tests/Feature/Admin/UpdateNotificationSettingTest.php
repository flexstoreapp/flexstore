<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Requests\Admin\UpdateNotificationSettingRequest;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(NotificationSettingController::class, UpdateNotificationSettingRequest::class, UpdateSettingsAction::class);

uses()->group('setting');

test('displays notification settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.notification.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/notification')
                ->has('settings')
        );
});

test('can update notification settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.notification.update'), [
        'notification_admin_new_order' => false,
        'notification_admin_order_canceled' => false,
        'notification_admin_low_stock' => true,
        'notification_admin_new_customer' => true,
        'notification_admin_new_review' => false,
        'notification_customer_order_confirmed' => false,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'notification_admin_new_order',
        'value' => '0',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'notification_admin_new_customer',
        'value' => '1',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'notification_customer_order_confirmed',
        'value' => '0',
    ]);
});

test('validates notification settings are boolean', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.notification.update'), [
        'notification_admin_new_order' => 'invalid',
    ]);

    $response->assertSessionHasErrors('notification_admin_new_order');
});

test('requires authentication', function () {
    $response = get(route('admin.settings.notification.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.notification.update'), [
        'notification_admin_new_order' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.notification.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.notification.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.notification.update'), [
        'notification_admin_new_order' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'notification_admin_new_order',
        'value' => '0',
    ]);

    $role->revokePermissionTo(Permission::SettingsNotificationConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.notification.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.notification.update'), [
        'notification_admin_new_order' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', [
        'key' => 'notification_admin_new_order',
        'value' => '0',
    ]);
});
