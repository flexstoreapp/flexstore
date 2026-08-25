<?php

declare(strict_types=1);

use App\Actions\ResetUserPasswordAction;
use App\Actions\SendPasswordResetLinkAction;
use App\Http\Controllers\Storefront\NewPasswordController;
use App\Http\Controllers\Storefront\PasswordResetLinkController;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\post;

covers(
    PasswordResetLinkController::class,
    SendPasswordResetLinkRequest::class,
    NewPasswordController::class,
    ResetPasswordRequest::class,
    SendPasswordResetLinkAction::class,
    ResetUserPasswordAction::class,
);

uses()->group('auth', 'account');

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('account.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $response = post(route('account.password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('account.login'))
            ->assertSessionHasNoErrors();

        return true;
    });
});

test('storefront reset link points to the storefront route even for admin accounts', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('account.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        expect($notification->admin)->toBeFalse()
            ->and($notification->toMail($user)->render())->toContain(route('account.password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ], false));

        return true;
    });
});

test('password reset fails with invalid token', function () {
    $user = User::factory()->create();

    $response = post(route('account.password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('email');
});

test('password reset fails with wrong email', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('account.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) {
        $response = post(route('account.password.store'), [
            'token' => $notification->token,
            'email' => 'wrong@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirectBack()
            ->assertInvalid('email');

        return true;
    });
});

test('password reset requires password confirmation', function () {
    Notification::fake();

    $user = User::factory()->create();

    post(route('account.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $response = post(route('account.password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password123',
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

    post(route('account.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $response = post(route('account.password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertRedirectBack()
            ->assertInvalid('password');

        return true;
    });
});

test('forgot password does not reveal whether email exists', function () {
    post(route('account.password.email'), ['email' => 'nonexistent@example.com'])
        ->assertRedirect()
        ->assertSessionHas('message');
});

test('requesting a reset link is rate limited to five attempts a minute', function () {
    Notification::fake();

    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        post(route('account.password.email'), ['email' => $user->email])
            ->assertRedirect();
    }

    post(route('account.password.email'), ['email' => $user->email])
        ->assertTooManyRequests();
});

test('resetting a password is rate limited to five attempts a minute', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        post(route('account.password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirectBack();
    }

    post(route('account.password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertTooManyRequests();
});
