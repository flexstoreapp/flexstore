<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\PolicySettingController;
use App\Http\Requests\Admin\UpdatePolicySettingRequest;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(PolicySettingController::class, UpdatePolicySettingRequest::class, UpdateSettingsAction::class);

uses()->group('setting');

test('displays policy settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.policy.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/policy')
                ->has('settings')
        );
});

test('can update policy settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.policy.update'), [
        'refund_policy' => 'Updated Refund Policy',
        'privacy_policy' => 'Updated Privacy Policy',
        'terms_of_service' => 'Updated Terms of Service',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'refund_policy',
        'value' => 'Updated Refund Policy',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'privacy_policy',
        'value' => 'Updated Privacy Policy',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'terms_of_service',
        'value' => 'Updated Terms of Service',
    ]);
});

test('requires authentication', function () {
    $response = get(route('admin.settings.policy.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.policy.update'), [
        'refund_policy' => 'Test Refund Policy',
        'privacy_policy' => 'Test Privacy Policy',
        'terms_of_service' => 'Test Terms of Service',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.policy.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.policy.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.policy.update'), [
        'refund_policy' => 'Test Refund Policy',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'refund_policy',
        'value' => 'Test Refund Policy',
    ]);

    $role->revokePermissionTo(Permission::SettingsPolicyConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.policy.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.policy.update'), [
        'refund_policy' => 'Forbidden Refund Policy',
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', [
        'key' => 'refund_policy',
        'value' => 'Test Refund Policy',
    ]);
});
