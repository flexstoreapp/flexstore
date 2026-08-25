<?php

declare(strict_types=1);

use App\Actions\DeleteCustomerAccountAction;
use App\Actions\UpdateUserAction;
use App\Http\Controllers\Storefront\ProfileController;
use App\Http\Requests\Storefront\DestroyProfileRequest;
use App\Http\Requests\Storefront\UpdateAccountProfileRequest;
use App\Models\User;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(
    ProfileController::class,
    UpdateAccountProfileRequest::class,
    DestroyProfileRequest::class,
    UpdateUserAction::class,
    DeleteCustomerAccountAction::class,
);

uses()->group('account');

test('profile edit page requires authentication', function () {
    get(route('account.profile.edit'))
        ->assertRedirect(route('account.login'));
});

test('profile edit page can be rendered', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/account/profile'));
});

test('profile update requires authentication', function () {
    patch(route('account.profile.update'), [])
        ->assertRedirect(route('account.login'));
});

test('profile can be updated', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->assertRedirect();

    assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);
});

test('profile update validates required fields', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => '',
            'email' => '',
        ])
        ->assertSessionHasErrors(['name', 'email']);
});

test('profile update validates email format', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
        ])
        ->assertSessionHasErrors('email');
});

test('profile update validates unique email', function () {
    $user = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Test User',
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('profile can keep the same email', function () {
    $user = User::factory()->create(['email' => 'same@example.com']);

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'same@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});

test('email change clears verification status', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
        ])
        ->assertRedirect();

    expect($user->fresh())
        ->email->toBe('new@example.com')
        ->email_verified_at->toBeNull();
});

test('email change sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
        ])
        ->assertRedirect();

    Notification::assertSentTo($user->fresh(), CustomerVerifyEmailNotification::class);
});

test('keeping same email does not send verification notification', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => now(),
    ]);

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'same@example.com',
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($user, CustomerVerifyEmailNotification::class);
});

test('keeping same email preserves verification status', function () {
    $verifiedAt = now()->subDays(10);
    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => $verifiedAt,
    ]);

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'same@example.com',
        ])
        ->assertRedirect();

    expect($user->fresh()->email_verified_at->toDateTimeString())
        ->toBe($verifiedAt->toDateTimeString());
});

test('account can be deleted with correct password', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->delete(route('account.profile.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    assertGuest();
    assertDatabaseMissing('users', ['id' => $user->id]);
});

test('account cannot be deleted with incorrect password', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->delete(route('account.profile.destroy'), [
            'password' => 'wrong-password',
        ])
        ->assertRedirectBack()
        ->assertInvalid('password');

    assertDatabaseHas('users', ['id' => $user->id]);
});

test('super admin account cannot be deleted', function () {
    $user = User::factory()->create();
    $user->assignRole(App\Enums\Role::SuperAdmin->value);

    actingAs($user)
        ->delete(route('account.profile.destroy'), [
            'password' => 'password',
        ])
        ->assertForbidden();

    assertDatabaseHas('users', ['id' => $user->id]);
});

test('account deletion requires authentication', function () {
    delete(route('account.profile.destroy'), [
        'password' => 'password',
    ])->assertRedirect(route('account.login'));
});
