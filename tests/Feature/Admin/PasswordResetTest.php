<?php

declare(strict_types=1);

use App\Actions\ResetUserPasswordAction;
use App\Actions\SendPasswordResetLinkAction;
use App\Http\Controllers\Admin\NewPasswordController;
use App\Http\Controllers\Admin\PasswordResetLinkController;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(
    PasswordResetLinkController::class,
    SendPasswordResetLinkRequest::class,
    NewPasswordController::class,
    ResetPasswordRequest::class,
    SendPasswordResetLinkAction::class,
    ResetUserPasswordAction::class,
);

uses()->group('auth');

test('displays reset password link screen', function () {
    $response = get(route('admin.password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('admin.password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('admin reset link points to the admin route', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('admin.password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        expect($notification->admin)->toBeTrue()
            ->and($notification->toMail($user)->render())->toContain(route('admin.password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ], false));

        return true;
    });
});

test('new password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('admin.password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) {
        $response = get(route('admin.password.reset', $notification->token));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('admin.password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $response = post(route('admin.password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.login'))
            ->assertSessionHasNoErrors();

        return true;
    });
});

test('password reset requires valid email', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('admin.password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) {
        $response = post(route('admin.password.store'), [
            'token' => $notification->token,
            'email' => 'wrong@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirectBack()
            ->assertInvalid('email');

        return true;
    });
});

test('password reset requires valid token', function () {
    $user = User::factory()->create();

    $response = post(route('admin.password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('email');
});

test('password reset requires password confirmation', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('admin.password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $response = post(route('admin.password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertRedirectBack()
            ->assertInvalid('password');

        return true;
    });
});

test('password must meet minimum requirements', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('admin.password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $response = post(route('admin.password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => '123', // Too short
            'password_confirmation' => '123',
        ]);

        $response->assertRedirectBack()
            ->assertInvalid('password');

        return true;
    });
});
