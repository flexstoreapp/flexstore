<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ConfirmablePasswordController;
use App\Http\Requests\Admin\ConfirmPasswordRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;

covers(ConfirmablePasswordController::class, ConfirmPasswordRequest::class);

uses()->group('auth');

test('displays confirm password screen', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get(route('admin.password.confirm'));

    $response->assertOk();
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('admin.password.confirm'), [
        'password' => 'password',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('admin.password.confirm'), [
        'password' => 'wrong-password',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('password');
});
