<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Requests\Admin\UpdateSeoSettingRequest;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(SeoSettingController::class, UpdateSeoSettingRequest::class, UpdateSettingsAction::class);

uses()->group('setting');

test('displays SEO settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.seo.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/seo')
                ->has('settings')
        );
});

test('can update SEO settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.seo.update'), [
        'seo_homepage_meta_title' => 'My Store - Best Products Online',
        'seo_homepage_meta_description' => 'Shop the best products at great prices.',
        'seo_shop_meta_title' => 'All Products',
        'seo_shop_meta_description' => 'Browse the full catalog.',
        'seo_robots_indexing' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'seo_homepage_meta_title',
        'value' => 'My Store - Best Products Online',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'seo_homepage_meta_description',
        'value' => 'Shop the best products at great prices.',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'seo_shop_meta_title',
        'value' => 'All Products',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'seo_shop_meta_description',
        'value' => 'Browse the full catalog.',
    ]);
});

test('validates meta description max length', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.seo.update'), [
        'seo_homepage_meta_description' => str_repeat('a', 501),
    ]);

    $response->assertSessionHasErrors('seo_homepage_meta_description');
});

test('requires authentication', function () {
    $response = get(route('admin.settings.seo.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.seo.update'), [
        'seo_homepage_meta_title' => 'Test Title',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.seo.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.seo.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.seo.update'), [
        'seo_homepage_meta_title' => 'Test Title',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'seo_homepage_meta_title',
        'value' => 'Test Title',
    ]);

    $role->revokePermissionTo(Permission::SettingsSeoConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.seo.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.seo.update'), [
        'seo_homepage_meta_title' => 'Forbidden Title',
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', [
        'key' => 'seo_homepage_meta_title',
        'value' => 'Test Title',
    ]);
});
