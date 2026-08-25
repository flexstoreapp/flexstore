<?php

declare(strict_types=1);

use App\Actions\UpdateUserAction;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Requests\Admin\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(ProfileController::class, UpdateProfileRequest::class, UpdateUserAction::class);

uses()->group('account');

test('displays profile edit page', function () {
    $response = actingAsSuperAdmin()->get(route('admin.profile.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/account/profile')
        );
});

test('profile information can be updated', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.profile.update'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = Auth::user();
    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});

test('profile information cannot be updated with invalid data', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.profile.update'), [
        'name' => '',
        'email' => 'test@example.com',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('profile information cannot be updated with invalid email', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.profile.update'), [
        'name' => 'Test User',
        'email' => 'invalid-email',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('email');
});

test('requires authentication to update profile', function () {
    $response = get(route('admin.profile.edit'));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.profile.update'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect(route('admin.login'));
});
