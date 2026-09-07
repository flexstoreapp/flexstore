<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\NewPasswordController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\PasswordResetLinkController;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

covers(
    PasswordController::class,
    PasswordResetLinkController::class,
    NewPasswordController::class,
    UpdatePasswordRequest::class,
);

uses()->group('api');

test('a reset link is sent for a known account', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    postJson(route('api.v1.auth.forgot-password'), ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('the reset link response does not reveal whether the account exists', function (): void {
    Notification::fake();

    postJson(route('api.v1.auth.forgot-password'), ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('message', __('A reset link will be sent if the account exists.'));

    Notification::assertNothingSent();
});

test('a password can be reset with a valid token', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    postJson(route('api.v1.auth.reset-password'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-1234',
        'password_confirmation' => 'new-password-1234',
    ])->assertOk();

    expect(Hash::check('new-password-1234', $user->refresh()->password))->toBeTrue();
});

test('a password reset fails with an invalid token', function (): void {
    $user = User::factory()->create();

    postJson(route('api.v1.auth.reset-password'), [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'new-password-1234',
        'password_confirmation' => 'new-password-1234',
    ])->assertJsonValidationErrors('email');
});

test('a signed in customer can change their password', function (): void {
    $user = User::factory()->create(['password' => 'password-1234']);

    Sanctum::actingAs($user, [TokenAbility::Customer->value]);

    putJson(route('api.v1.account.password.update'), [
        'current_password' => 'password-1234',
        'password' => 'new-password-1234',
        'password_confirmation' => 'new-password-1234',
    ])->assertOk();

    expect(Hash::check('new-password-1234', $user->refresh()->password))->toBeTrue();
});

test('changing the password requires the current one', function (): void {
    $user = User::factory()->create(['password' => 'password-1234']);

    Sanctum::actingAs($user, [TokenAbility::Customer->value]);

    putJson(route('api.v1.account.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password-1234',
        'password_confirmation' => 'new-password-1234',
    ])->assertJsonValidationErrors('current_password');
});
