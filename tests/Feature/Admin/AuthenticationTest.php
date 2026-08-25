<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SessionController;
use App\Http\Requests\Admin\StoreSessionRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(SessionController::class, StoreSessionRequest::class);

uses()->group('auth');

test('displays login page', function () {
    $response = get(route('admin.login'));

    $response->assertOk();
});

test('users can authenticate', function () {
    $user = User::factory()->create();

    $response = post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard', absolute: false));

    assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'))
        ->assertSessionHasNoErrors();

    assertGuest();
});

test('login requests are rate limited after too many attempts', function () {
    Event::fake();

    $user = User::factory()->create();
    $email = $user->email;

    Cache::flush();

    // Simulate 5 failed login attempts
    for ($i = 0; $i < 5; $i++) {
        post(route('admin.login'), [
            'email' => $email,
            'password' => 'wrong-password',
        ]);
    }

    // The 6th attempt should trigger rate limiting
    $response = post(route('admin.login'), [
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('email');

    Event::assertDispatched(Lockout::class);
});

test('unknown email returns auth.failed without 404 and hits the throttle', function () {
    Event::fake(Lockout::class);
    Cache::flush();

    // 5 unknown-email attempts must each return validation error and increment the throttle
    for ($i = 0; $i < 5; $i++) {
        $response = post(route('admin.login'), [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $response->assertRedirectBack()->assertInvalid('email');
    }

    // 6th attempt trips the per-email+ip lockout
    $response = post(route('admin.login'), [
        'email' => 'nobody@example.com',
        'password' => 'whatever',
    ]);

    $response->assertRedirectBack()->assertInvalid('email');

    Event::assertDispatched(Lockout::class);
});

test('users with 2FA enabled are redirected to the two-factor challenge', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'encrypted-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $response = post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.two-factor.challenge'))
        ->assertSessionHas('login.id', $user->id);

    assertGuest();
});

test('users with 2FA rejected credentials returns to login with validation errors', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'encrypted-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $response = post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirectBack()->assertInvalid('email');

    assertGuest();
});
