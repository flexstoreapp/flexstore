<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\MailSettingController;
use App\Http\Requests\Admin\UpdateMailSettingRequest;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(MailSettingController::class, UpdateMailSettingRequest::class, UpdateSettingsAction::class);

uses()->group('setting');

test('displays mail settings page', function () {
    $response = actingAsSuperAdmin()
        ->get(route('admin.settings.mail.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/mail')
                ->has('settings')
        );
});

test('pre-fills empty from address and from name with store email and store name', function () {
    Setting::setValue('store_email', 'shop@example.com');
    Setting::setValue('store_name', 'Example Shop');

    actingAsSuperAdmin()
        ->get(route('admin.settings.mail.show'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('settings.mail_from_address', 'shop@example.com')
                ->where('settings.mail_from_name', 'Example Shop')
        );
});

test('does not override saved from address and from name with store values', function () {
    Setting::setValue('store_email', 'shop@example.com');
    Setting::setValue('store_name', 'Example Shop');
    Setting::setValue('mail_from_address', 'mailer@example.com');
    Setting::setValue('mail_from_name', 'Mailer');

    actingAsSuperAdmin()
        ->get(route('admin.settings.mail.show'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('settings.mail_from_address', 'mailer@example.com')
                ->where('settings.mail_from_name', 'Mailer')
        );
});

test('can update mail settings', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.settings.mail.update'), [
        'mail_host' => 'smtp.example.com',
        'mail_port' => 587,
        'mail_encryption' => 'tls',
        'mail_username' => 'user@example.com',
        'mail_password' => 'secret-password',
        'mail_from_address' => 'hello@example.com',
        'mail_from_name' => 'Example Store',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', ['key' => 'mail_host', 'value' => 'smtp.example.com']);
    assertDatabaseHas('settings', ['key' => 'mail_port', 'value' => 587]);
    assertDatabaseHas('settings', ['key' => 'mail_encryption', 'value' => 'tls']);
    assertDatabaseHas('settings', ['key' => 'mail_from_address', 'value' => 'hello@example.com']);
    assertDatabaseHas('settings', ['key' => 'mail_from_name', 'value' => 'Example Store']);

    expect(Setting::getValue('mail_password'))->toBe('secret-password');

    $rawPassword = Setting::query()->where('key', 'mail_password')->value('value');
    expect($rawPassword)->not->toBe('secret-password');
});

test('null values fall back to env defaults', function () {
    Setting::setValue('mail_host', 'smtp.example.com');

    $response = actingAsSuperAdmin()->patch(route('admin.settings.mail.update'), [
        'mail_host' => null,
        'mail_port' => null,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', ['key' => 'mail_host', 'value' => null]);
    assertDatabaseHas('settings', ['key' => 'mail_port', 'value' => null]);
});

test('cannot update mail settings with invalid data', function () {
    $response = actingAsSuperAdmin()
        ->patch(route('admin.settings.mail.update'), [
            'mail_port' => 99999,
            'mail_encryption' => 'aes',
            'mail_from_address' => 'not-an-email',
        ]);

    $response->assertRedirectBack()
        ->assertInvalid(['mail_port', 'mail_encryption', 'mail_from_address']);
});

test('requires authentication', function () {
    $response = get(route('admin.settings.mail.show'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.settings.mail.update'), ['mail_host' => 'smtp.example.com']);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.mail.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.mail.show'));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.settings.mail.update'), [
        'mail_host' => 'smtp.example.com',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', ['key' => 'mail_host', 'value' => 'smtp.example.com']);

    $role->revokePermissionTo(Permission::SettingsMailConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.mail.show'));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.settings.mail.update'), [
        'mail_host' => 'smtp.other.com',
    ]);

    $response->assertForbidden();

    assertDatabaseHas('settings', ['key' => 'mail_host', 'value' => 'smtp.example.com']);
});
