<?php

declare(strict_types=1);

use App\Actions\CompleteTwoFactorLoginAction;
use App\Http\Controllers\Admin\TwoFactorChallengeController;
use App\Http\Requests\Admin\TwoFactorChallengeRequest;
use App\Models\User;
use App\TwoFactor\RecoveryCode;
use App\TwoFactor\Totp;
use App\Utilities\LoginRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(TwoFactorChallengeController::class, TwoFactorChallengeRequest::class, CompleteTwoFactorLoginAction::class);

uses()->group('auth', 'two-factor');

test('user with 2FA enabled is redirected to challenge after login', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = get(route('admin.two-factor.challenge'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/auth/two-factor-challenge')
        );
});

test('user without 2FA enabled cannot access challenge page', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = get(route('admin.two-factor.challenge'));

    $response->assertNotFound();
});

test('user without 2FA enabled cannot submit challenge form', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'code' => '123456',
    ]);

    $response->assertNotFound();
});

test('user can authenticate with valid TOTP code', function () {
    $totp = app(Totp::class);
    $secret = $totp->generateSecret();

    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $validCode = $totp->getCurrentCode($secret);

    $response = post(route('admin.two-factor.challenge.store'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('admin.dashboard'))
        ->assertSessionHasNoErrors();

    assertAuthenticated();
    expect(session()->has('login.id'))->toBeFalse();
});

test('user cannot authenticate with invalid TOTP code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'code' => '000000',
    ]);

    $response->assertRedirect()
        ->assertInvalid('code');

    assertGuest();
    expect(session()->has('login.id'))->toBeTrue();
});

test('user can authenticate with valid recovery code', function () {
    $recoveryCode = app(RecoveryCode::class);
    $recoveryCodes = $recoveryCode->generate();

    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $recoveryCodes,
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'recovery_code' => $recoveryCodes[0],
    ]);

    $response->assertRedirect(route('admin.dashboard'))
        ->assertSessionHasNoErrors();

    assertAuthenticated();
    expect(session()->has('login.id'))->toBeFalse();

    // Verify recovery code was consumed
    $user->refresh();
    $remainingCodes = $user->two_factor_recovery_codes;
    expect($remainingCodes)->not->toContain($recoveryCodes[0])
        ->and($remainingCodes)->toContain($recoveryCodes[1]);
});

test('user cannot authenticate with invalid recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['recovery1234-abcdefgh', 'recovery5678-jklmnopq'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'recovery_code' => 'invalid-code',
    ]);

    $response->assertRedirect()
        ->assertInvalid('code');

    assertGuest();
    expect(session()->has('login.id'))->toBeTrue();
});

test('user cannot authenticate with used recovery code', function () {
    $recoveryCode = app(RecoveryCode::class);
    $recoveryCodes = $recoveryCode->generate();
    $usedCode = array_pop($recoveryCodes); // Remove one code to simulate it being used

    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $recoveryCodes, // Only remaining codes
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'recovery_code' => $usedCode, // This code was already used (not in the list)
    ]);

    $response->assertRedirect()
        ->assertInvalid('code');

    assertGuest();
    expect(session()->has('login.id'))->toBeTrue();
});

test('user cannot provide both TOTP code and recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['recovery1234-abcdefgh'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'code' => '123456',
        'recovery_code' => 'recovery1234-abcdefgh',
    ]);

    $response->assertRedirect()
        ->assertInvalid('code');

    assertGuest();
});

test('user must provide either TOTP code or recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['recovery1234-abcdefgh'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), []);

    $response->assertRedirect()
        ->assertInvalid('code');

    assertGuest();
});

test('challenge requests are rate limited', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['recovery1234-abcdefgh'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    // Make 6 failed attempts (rate limit is 6 per minute)
    for ($i = 0; $i < 6; $i++) {
        post(route('admin.two-factor.challenge.store'), [
            'code' => '000000',
        ]);
    }

    // The 7th attempt should be rate limited
    $response = post(route('admin.two-factor.challenge.store'), [
        'code' => '000000',
    ]);

    $response->assertStatus(429); // Too Many Requests
});

test('login rate limiter is cleared after a successful challenge', function () {
    $totp = app(Totp::class);
    $secret = $totp->generateSecret();

    $user = User::factory()->create([
        'email' => 'rl@example.com',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => app(RecoveryCode::class)->generate(),
    ]);

    $request = Request::create('/admin/two-factor-challenge', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
    $key = Str::transliterate(Str::lower($user->email) . '|127.0.0.1');

    app(LoginRateLimiter::class)->hit($user->email, $request);
    app(LoginRateLimiter::class)->hit($user->email, $request);
    expect(RateLimiter::attempts($key))->toBe(2);

    session(['login.id' => $user->id, 'login.remember' => false]);

    post(route('admin.two-factor.challenge.store'), [
        'code' => $totp->getCurrentCode($secret),
    ])->assertRedirect(route('admin.dashboard'));

    expect(RateLimiter::attempts($key))->toBe(0);
});

test('user with remember me option is remembered after 2FA', function () {
    $totp = app(Totp::class);
    $secret = $totp->generateSecret();

    $user = User::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    // Simulate login attempt with remember me
    session(['login.id' => $user->id, 'login.remember' => true]);

    $validCode = $totp->getCurrentCode($secret);

    $response = post(route('admin.two-factor.challenge.store'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('admin.dashboard'))
        ->assertSessionHasNoErrors();

    assertAuthenticated();
    expect($user->fresh()->remember_token)->not->toBeNull();
});

test('challenge page shows proper validation errors for TOTP code format', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['recovery1234-abcdefgh'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'code' => 'abc123', // Invalid format
    ]);

    $response->assertRedirect()
        ->assertInvalid('code');
});

test('challenge page shows proper validation errors for recovery code format', function () {
    $user = User::factory()->create([
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['recovery1234-abcdefgh'],
    ]);

    // Simulate login attempt
    session(['login.id' => $user->id, 'login.remember' => false]);

    $response = post(route('admin.two-factor.challenge.store'), [
        'recovery_code' => [12122], // String is expected
    ]);

    $response->assertRedirect()
        ->assertInvalid('recovery_code');
});
