<?php

declare(strict_types=1);

use App\Actions\ConfirmTwoFactorAuthenticationAction;
use App\Actions\EnableTwoFactorAuthenticationAction;
use App\Actions\RegenerateRecoveryCodesAction;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\TwoFactorAuthenticationController;
use App\Http\Controllers\Admin\TwoFactorConfirmationController;
use App\Http\Controllers\Admin\TwoFactorQrCodeController;
use App\Http\Controllers\Admin\TwoFactorRecoveryCodesController;
use App\Http\Controllers\Admin\TwoFactorSecretKeyController;
use App\Http\Requests\Admin\ConfirmTwoFactorRequest;
use App\TwoFactor\Totp;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers([
    SecurityController::class,
    TwoFactorAuthenticationController::class,
    TwoFactorConfirmationController::class,
    TwoFactorQrCodeController::class,
    TwoFactorSecretKeyController::class,
    TwoFactorRecoveryCodesController::class,
    EnableTwoFactorAuthenticationAction::class,
    ConfirmTwoFactorAuthenticationAction::class,
    ConfirmTwoFactorRequest::class,
    RegenerateRecoveryCodesAction::class,
]);

uses()->group('auth', 'two-factor');

test('user is redirected to password confirm page first', function () {
    $testCase = actingAsSuperAdmin();

    // Password is not confirmed
    $testCase->session(['auth.password_confirmed_at' => null]);

    $response = $testCase->get(route('admin.security.show'));

    $response->assertRedirect(route('admin.password.confirm'));
});

test('user can view two factor authentication page with password confirmed', function () {
    $testCase = actingAsSuperAdmin();

    // Confirm password to bypass middleware
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->get(route('admin.security.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/account/security')
                ->has('requiresConfirmation')
                ->where('requiresConfirmation', false)
                ->has('twoFactorEnabled')
                ->where('twoFactorEnabled', false)
                ->has('recoveryCodes')
                ->where('recoveryCodes', [])
        );
});

test('user can enable two factor authentication', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->post(route('admin.two-factor.store'));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = Auth::user();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasPendingTwoFactorConfirmation())->toBeTrue();
});

test('user can get qr code after enabling two factor authentication', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $response = $testCase->get(route('admin.two-factor.qr-code'));

    $response->assertOk()
        ->assertJsonStructure([
            'svg',
        ]);

    $data = $response->json();
    expect($data['svg'])->toBeString()
        ->and($data['svg'])->toContain('<svg');
});

test('user cannot get qr code without enabling two factor authentication first', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->get(route('admin.two-factor.qr-code'));

    $response->assertStatus(403);
});

test('user can get secret key after enabling two factor authentication', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $response = $testCase->get(route('admin.two-factor.secret-key'));

    $response->assertOk()
        ->assertJsonStructure([
            'secretKey',
        ]);

    $data = $response->json();
    expect($data['secretKey'])->toBeString()
        ->and(mb_strlen($data['secretKey']))->toBeGreaterThan(15); // Formatted with spaces
});

test('user cannot get secret key without enabling two factor authentication first', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->get(route('admin.two-factor.secret-key'));

    $response->assertStatus(403);
});

test('user can confirm two factor authentication with valid code', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $user = Auth::user();
    $validCode = app(Totp::class)->getCurrentCode($user->two_factor_secret);

    $response = $testCase->post(route('admin.two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->two_factor_confirmed_at)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->not->toBeNull()
        ->and($user->hasTwoFactorEnabled())->toBeTrue();
});

test('user cannot confirm two factor authentication with invalid code', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $response = $testCase->post(route('admin.two-factor.confirm'), [
        'code' => '000000',
    ]);

    $response->assertRedirect()
        ->assertSessionHasErrors(['code']);

    $user = Auth::user();
    expect($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasTwoFactorEnabled())->toBeFalse();
});

test('user cannot confirm two factor authentication without pending setup', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->post(route('admin.two-factor.confirm'), [
        'code' => '123456',
    ]);

    $response->assertStatus(403);
});

test('two factor authentication status shows enabled after confirmation', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable and confirm 2FA
    $testCase->post(route('admin.two-factor.store'));

    $user = Auth::user();
    $validCode = app(Totp::class)->getCurrentCode($user->two_factor_secret);

    $testCase->post(route('admin.two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $response = $testCase->get(route('admin.security.show'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/account/security')
                ->where('twoFactorEnabled', true)
        );
});

test('confirmation requires valid code format', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $response = $testCase->post(route('admin.two-factor.confirm'), [
        'code' => 'invalid',
    ]);

    $response->assertRedirect()
        ->assertSessionHasErrors(['code']);
});

test('confirmation requires code to be provided', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $response = $testCase->post(route('admin.two-factor.confirm'), []);

    $response->assertRedirect()
        ->assertSessionHasErrors(['code']);
});

test('requires authentication', function () {
    $response = get(route('admin.security.show'));
    $response->assertRedirect(route('admin.login'));

    $response = post(route('admin.two-factor.store'));
    $response->assertRedirect(route('admin.login'));

    $response = post(route('admin.two-factor.confirm'), [
        'code' => '123456',
    ]);
    $response->assertRedirect(route('admin.login'));
});
