<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\LanguageSettingController;
use App\Http\Requests\Admin\UpdateLanguageSettingRequest;
use App\Models\Setting;
use App\Rules\DefaultInAvailableRule;
use App\Rules\ValidLocale;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers([
    LanguageSettingController::class,
    UpdateLanguageSettingRequest::class,
    ValidLocale::class,
    DefaultInAvailableRule::class,
    UpdateSettingsAction::class,
]);

uses()->group('setting');

test('a fresh install starts with the default locale only', function () {
    expect(Setting::getValue('available_locales'))->toBe(['en'])
        ->and(Setting::getValue('default_locale'))->toBe('en');
});

test('displays language settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.language.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/language')
                ->has('settings')
                ->has('availableLanguages')
        );
});

test('can update language settings', function () {
    installTestLocale('zzls');

    $response = actingAsSuperAdmin()->patch(route('admin.settings.language.update'), [
        'default_locale' => 'en',
        'available_locales' => ['en', 'zzls'],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'default_locale',
        'value' => 'en',
    ]);

    assertDatabaseHas('settings', [
        'key' => 'available_locales',
        'value' => json_encode(['en', 'zzls']),
    ]);
})->after(fn () => removeTestLocale('zzls'));

test('validates default locale has a translation file', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.language.update'), [
        'default_locale' => 'xx',
        'available_locales' => ['en'],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('default_locale');
});

test('validates available locales have translation files', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.language.update'), [
        'default_locale' => 'en',
        'available_locales' => ['en', 'xx'],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('available_locales.1');
});

test('validates default locale must be in available locales', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.language.update'), [
        'default_locale' => 'bn',
        'available_locales' => ['en', 'ar'],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('default_locale');
});

test('validates available locales is required and non-empty', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.language.update'), [
        'default_locale' => 'en',
        'available_locales' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('available_locales');
});

test('requires authentication', function () {
    get(route('admin.settings.language.show'))
        ->assertRedirect(route('admin.login'));

    patch(route('admin.settings.language.update'), [
        'default_locale' => 'en',
        'available_locales' => ['en'],
    ])->assertRedirect(route('admin.login'));
});

test('requires settings.language.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    actingAsAdmin()->get(route('admin.settings.language.show'))
        ->assertOk();

    $role->revokePermissionTo(Permission::SettingsLanguageConfigure);

    actingAsAdmin()->get(route('admin.settings.language.show'))
        ->assertForbidden();
});
