<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Address\SellingCountries;
use App\Enums\Country;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StoreSettingController;
use App\Http\Requests\Admin\UpdateStoreSettingRequest;
use App\Models\Media;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(StoreSettingController::class, UpdateStoreSettingRequest::class, UpdateSettingsAction::class);

uses()->group('setting');

test('displays store settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.store.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/store')
                ->has('settings')
        );
});

test('exposes the configured asset media on the store settings page', function () {
    $media = Media::factory()->create();

    actingAsSuperAdmin()->patch(route('admin.settings.store.update'), ['store_logo' => $media->id]);

    actingAsSuperAdmin()
        ->get(route('admin.settings.store.show'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/store')
                ->where('assetMedia.store_logo.id', $media->id)
                ->where('assetMedia.store_favicon', null)
        );
});

test('shows the store description for the active locale', function () {
    Setting::setValue('available_locales', ['en', 'ar']);
    Setting::setValue('store_description', json_encode([
        'en' => 'The best shop in town.',
        'ar' => 'أفضل متجر في المدينة.',
    ], JSON_UNESCAPED_UNICODE));

    actingAsSuperAdmin()
        ->get(route('admin.settings.store.show', ['lang' => 'ar']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settings.store_description', 'أفضل متجر في المدينة.')
        );
});

test('merges a localized store description instead of replacing other locales', function () {
    Setting::setValue('available_locales', ['en', 'ar']);
    Setting::setValue('store_description', json_encode([
        'en' => 'The best shop in town.',
        'ar' => 'قديم',
    ], JSON_UNESCAPED_UNICODE));

    actingAsSuperAdmin()
        ->patch(route('admin.settings.store.update', ['lang' => 'ar']), [
            'store_description' => 'أفضل متجر في المدينة.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(json_decode((string) Setting::getValue('store_description'), true))->toBe([
        'en' => 'The best shop in town.',
        'ar' => 'أفضل متجر في المدينة.',
    ]);
});

test('rejects an invalid store phone number', function () {
    actingAsSuperAdmin()
        ->patch(route('admin.settings.store.update'), [
            'store_phone' => 'not-a-phone',
        ])
        ->assertInvalid('store_phone');
});

test('can update store settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.store.update'), [
        'store_name' => 'Updated Store Name',
        'store_description' => 'Updated Store Description',
        'store_email' => 'updated@example.com',
        'store_phone' => '+14155552671',
        'store_street_address' => 'Updated Store Street Address',
        'store_city' => 'Updated Store City',
        'store_state' => 'Dhaka',
        'store_postal_code' => '1207',
        'store_country_code' => Country::BD->name,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'store_name',
        'value' => 'Updated Store Name',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_description',
        'value' => 'Updated Store Description',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_email',
        'value' => 'updated@example.com',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_phone',
        'value' => '+14155552671',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_street_address',
        'value' => 'Updated Store Street Address',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_city',
        'value' => 'Updated Store City',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_state',
        'value' => 'Dhaka',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_postal_code',
        'value' => '1207',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_country_code',
        'value' => Country::BD->name,
    ]);
});

test('can update the supported countries', function () {
    actingAsSuperAdmin()
        ->patch(route('admin.settings.store.update'), [
            'selling_countries' => ['BD', 'US'],
        ])
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'selling_countries',
        'value' => json_encode(['BD', 'US']),
    ]);

    expect(SellingCountries::codes())->toBe(['BD', 'US']);
});

test('can clear the supported countries so every country is supported', function () {
    Setting::setValue('selling_countries', ['BD']);

    actingAsSuperAdmin()
        ->patch(route('admin.settings.store.update'), [
            'selling_countries' => [],
        ])
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(SellingCountries::codes())->toBe(array_values(Country::codes()));
});

test('rejects an unknown supported country code', function () {
    actingAsSuperAdmin()
        ->patch(route('admin.settings.store.update'), [
            'selling_countries' => ['ZZ'],
        ])
        ->assertInvalid('selling_countries.0');
});

test('can update store logo', function () {
    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.settings.store.update'), [
        'store_logo' => $media->id,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'store_logo',
        'value' => (string) $media->id,
    ]);
});

test('can update dark mode logo', function () {
    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.settings.store.update'), [
        'store_logo_dark' => $media->id,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'store_logo_dark',
        'value' => (string) $media->id,
    ]);
});

test('can update store favicon', function () {
    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.settings.store.update'), [
        'store_favicon' => $media->id,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'store_favicon',
        'value' => (string) $media->id,
    ]);
});

test('store logo can be set to null', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.store.update'), [
        'store_logo' => null,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();
});

test('can update social media links', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.store.update'), [
        'store_social_facebook' => 'https://facebook.com/mystore',
        'store_social_instagram' => 'https://instagram.com/mystore',
        'store_social_x' => 'https://x.com/mystore',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'store_social_facebook',
        'value' => 'https://facebook.com/mystore',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_social_instagram',
        'value' => 'https://instagram.com/mystore',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'store_social_x',
        'value' => 'https://x.com/mystore',
    ]);
});

test('validates social link urls must be valid urls', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.store.update'), [
        'store_social_facebook' => 'not-a-url',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('store_social_facebook');
});

test('cannot update store settings with invalid data', function () {
    $response = actingAsSuperAdmin()
        ->patch(route('admin.settings.store.update'), [
            'store_name' => '',
            'store_email' => 'not-an-email',
            'store_phone' => '',
            'store_street_address' => '',
            'store_city' => '',
            'store_state' => '',
            'store_postal_code' => '',
            'store_country_code' => '',
            'store_description' => '',
        ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'store_name',
            'store_email',
            'store_phone',
            'store_street_address',
            'store_city',
            'store_postal_code',
            'store_country_code',
            'store_description',
        ]);
});

test('requires authentication', function () {
    $response = get(route('admin.settings.store.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.store.update'), [
        'store_name' => 'Test Store',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.store.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.store.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.store.update'), [
        'store_name' => 'Test Store',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'store_name',
        'value' => 'Test Store',
    ]);

    $role->revokePermissionTo(Permission::SettingsStoreConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.store.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.store.update'), [
        'store_name' => 'Forbidden Store',
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', [
        'key' => 'store_name',
        'value' => 'Test Store',
    ]);
});
