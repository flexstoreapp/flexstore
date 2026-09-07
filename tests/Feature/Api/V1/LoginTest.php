<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\AccessTokenController;
use App\Http\Requests\Api\V1\StoreAccessTokenRequest;
use App\Models\User;

use function Pest\Laravel\postJson;

covers(AccessTokenController::class, StoreAccessTokenRequest::class);

uses()->group('api');

test('a customer can exchange credentials for an access token', function (): void {
    $user = User::factory()->create(['password' => 'password-1234']);

    postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password-1234',
        'device_name' => 'Pixel 9',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

    expect($user->tokens()->sole()->abilities)->toBe([TokenAbility::Customer->value]);
});

test('login fails with the wrong password', function (): void {
    $user = User::factory()->create(['password' => 'password-1234']);

    postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'Pixel 9',
    ])->assertJsonValidationErrors('email');

    expect($user->tokens()->count())->toBe(0);
});

test('login fails for an unknown email without leaking that the account is missing', function (): void {
    postJson(route('api.v1.auth.login'), [
        'email' => 'nobody@example.com',
        'password' => 'password-1234',
        'device_name' => 'Pixel 9',
    ])->assertJsonValidationErrors(['email' => __('auth.failed')]);
});

test('logging out revokes only the current token', function (): void {
    $user = User::factory()->create();
    $current = $user->createToken('This device', [TokenAbility::Customer->value]);
    $other = $user->createToken('Other device', [TokenAbility::Customer->value]);

    postJson(route('api.v1.auth.logout'), [], [
        'Authorization' => 'Bearer ' . $current->plainTextToken,
    ])->assertOk();

    expect($user->tokens()->pluck('id')->all())->toBe([$other->accessToken->id]);
});

test('the token endpoints reject an unauthenticated request', function (): void {
    postJson(route('api.v1.auth.logout'))->assertUnauthorized();
});
