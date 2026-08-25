<?php

declare(strict_types=1);

use App\Actions\UpdateUserAction;
use App\Http\Controllers\Storefront\PasswordController;
use App\Http\Requests\UpdateCustomerPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

covers(PasswordController::class, UpdateCustomerPasswordRequest::class, UpdateUserAction::class);

uses()->group('account');

test('password update requires authentication', function () {
    put(route('account.password.update'), [])
        ->assertRedirect(route('account.login'));
});

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => 'current-password',
    ]);

    actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])
        ->assertRedirect();

    $user->refresh();
    expect(Hash::check('new-password123', $user->password))->toBeTrue();
});

test('correct current password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => 'current-password',
    ]);

    actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])
        ->assertSessionHasErrors('current_password');
});

test('password must be confirmed to update', function () {
    $user = User::factory()->create([
        'password' => 'current-password',
    ]);

    actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors('password');
});

test('password must meet minimum requirements', function () {
    $user = User::factory()->create([
        'password' => 'current-password',
    ]);

    actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'current-password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});
