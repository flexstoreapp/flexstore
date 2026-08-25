<?php

declare(strict_types=1);

use App\Actions\UpdateUserAction;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Requests\UpdateCustomerPasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

covers(PasswordController::class, UpdateCustomerPasswordRequest::class, UpdateUserAction::class);

uses()->group('account');

test('displays password update page', function () {
    $response = actingAsSuperAdmin()->get(route('admin.password.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/account/password')
        );
});

test('password can be updated', function () {
    $response = actingAsSuperAdmin()->put(route('admin.password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Hash::check('new-password', Auth::user()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $response = actingAsSuperAdmin()->put(route('admin.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('current_password');
});

test('requires authentication', function () {
    $response = get(route('admin.password.edit'));

    $response->assertRedirect(route('admin.login'));

    $response = put(route('admin.password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('admin.login'));
});
