<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\IntegrationSettingController;
use App\Http\Requests\Admin\UpdateIntegrationSettingRequest;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(IntegrationSettingController::class, UpdateIntegrationSettingRequest::class, UpdateSettingsAction::class);

uses()->group('setting');

test('displays integration settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.integration.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/integration')
                ->has('settings')
        );
});

test('can update integration settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_analytics_id' => 'G-1234567890',
        'integration_google_tag_manager_id' => 'GTM-ABCDEF',
        'integration_meta_pixel_id' => '123456789012345',
        'integration_tiktok_pixel_id' => 'CTTTTTTTTTTTTT',
        'integration_pinterest_tag_id' => '1234567890123',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'integration_google_analytics_id',
        'value' => 'G-1234567890',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'integration_google_tag_manager_id',
        'value' => 'GTM-ABCDEF',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'integration_meta_pixel_id',
        'value' => '123456789012345',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'integration_tiktok_pixel_id',
        'value' => 'CTTTTTTTTTTTTT',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'integration_pinterest_tag_id',
        'value' => '1234567890123',
    ]);
});

test('validates Google Analytics ID format', function () {
    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_analytics_id' => 'UA-12345',
    ])->assertSessionHasErrors('integration_google_analytics_id');

    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_analytics_id' => 'invalid',
    ])->assertSessionHasErrors('integration_google_analytics_id');
});

test('validates Google Tag Manager ID format', function () {
    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_tag_manager_id' => 'GT-12345',
    ])->assertSessionHasErrors('integration_google_tag_manager_id');

    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_tag_manager_id' => 'invalid',
    ])->assertSessionHasErrors('integration_google_tag_manager_id');
});

test('validates Meta Pixel ID format', function () {
    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_meta_pixel_id' => 'abc123',
    ])->assertSessionHasErrors('integration_meta_pixel_id');
});

test('validates TikTok Pixel ID format', function () {
    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_tiktok_pixel_id' => 'invalid-id!',
    ])->assertSessionHasErrors('integration_tiktok_pixel_id');
});

test('validates Pinterest Tag ID format', function () {
    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_pinterest_tag_id' => 'abc123',
    ])->assertSessionHasErrors('integration_pinterest_tag_id');
});

test('allows null values to disconnect integrations', function () {
    actingAsSuperAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_analytics_id' => null,
        'integration_meta_pixel_id' => null,
    ])->assertRedirect()->assertSessionHasNoErrors();
});

test('requires authentication', function () {
    $response = get(route('admin.settings.integration.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.integration.update'), [
        'integration_google_analytics_id' => 'G-TEST',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.integration.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.integration.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_analytics_id' => 'G-TEST',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'integration_google_analytics_id',
        'value' => 'G-TEST',
    ]);

    $role->revokePermissionTo(Permission::SettingsIntegrationConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.integration.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.integration.update'), [
        'integration_google_analytics_id' => 'G-FORBIDDEN',
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', [
        'key' => 'integration_google_analytics_id',
        'value' => 'G-TEST',
    ]);
});
