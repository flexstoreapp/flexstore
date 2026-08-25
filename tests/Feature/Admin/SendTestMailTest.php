<?php

declare(strict_types=1);

use App\Actions\SendTestMailAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\SendTestMailController;
use App\Http\Requests\Admin\SendTestMailRequest;
use App\Notifications\TestMailNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\post;

covers(SendTestMailController::class, SendTestMailRequest::class, SendTestMailAction::class);

uses()->group('setting');

test('sends a test email to the recipient', function () {
    Notification::fake();

    $response = actingAsSuperAdmin()
        ->post(route('admin.settings.mail.test'), ['recipient' => 'admin@example.com']);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertSentOnDemand(TestMailNotification::class, function (TestMailNotification $notification, array $channels, object $notifiable) {
        return in_array('mail', $channels, true)
            && $notifiable->routes['mail'] === 'admin@example.com';
    });
});

test('rejects invalid recipient', function () {
    Notification::fake();

    $response = actingAsSuperAdmin()
        ->post(route('admin.settings.mail.test'), ['recipient' => 'not-an-email']);

    $response->assertRedirectBack()
        ->assertInvalid(['recipient']);

    Notification::assertNothingSent();
});

test('requires authentication', function () {
    $response = post(route('admin.settings.mail.test'), ['recipient' => 'a@example.com']);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.mail.configure permission', function () {
    Notification::fake();

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()
        ->post(route('admin.settings.mail.test'), ['recipient' => 'admin@example.com']);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $role->revokePermissionTo(Permission::SettingsMailConfigure);

    $response = actingAsAdmin()
        ->post(route('admin.settings.mail.test'), ['recipient' => 'admin@example.com']);

    $response->assertForbidden();
});
