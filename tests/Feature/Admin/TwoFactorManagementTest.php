<?php

declare(strict_types=1);

use App\Actions\DisableTwoFactorAuthenticationAction;
use App\Actions\EnableTwoFactorAuthenticationAction;
use App\Actions\RegenerateRecoveryCodesAction;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\TwoFactorAuthenticationController;
use App\Http\Controllers\Admin\TwoFactorRecoveryCodesController;
use App\Models\User;
use App\TwoFactor\Totp;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers([
    SecurityController::class,
    TwoFactorAuthenticationController::class,
    DisableTwoFactorAuthenticationAction::class,
    TwoFactorRecoveryCodesController::class,
    RegenerateRecoveryCodesAction::class,
    EnableTwoFactorAuthenticationAction::class,
]);

uses()->group('auth', 'two-factor');

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

test('user can confirm two factor authentication with valid code', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $user = Auth::user();

    // Generate a valid TOTP code
    $validCode = app(Totp::class)->getCurrentCode($user->two_factor_secret);

    $response = $testCase->post(route('admin.two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

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

test('enabling 2FA generates proper secret and setup data', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $user = Auth::user()->refresh();

    // Verify user starts without 2FA
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->hasTwoFactorEnabled())->toBeFalse()
        ->and($user->hasPendingTwoFactorConfirmation())->toBeFalse();

    // Enable 2FA
    $testCase->post(route('admin.two-factor.store'));

    // Verify 2FA setup is in progress
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->hasPendingTwoFactorConfirmation())->toBeTrue()
        ->and($user->hasTwoFactorEnabled())->toBeFalse();

    // Verify secret can be decrypted
    $decryptedSecret = $user->two_factor_secret;
    expect($decryptedSecret)->toBeString()
        ->and(mb_strlen($decryptedSecret))->toBeGreaterThan(10); // Google2FA generates base32 secrets
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

test('unauthorized user cannot access 2FA management endpoints', function () {
    // Test without authentication
    $response = get(route('admin.security.show'));
    $response->assertRedirect(); // Should redirect to login

    $response = post(route('admin.two-factor.store'));
    $response->assertRedirect(); // Should redirect to login

    $response = delete(route('admin.two-factor.destroy'));
    $response->assertRedirect(); // Should redirect to login

    $response = post(route('admin.two-factor.recovery-codes.store'));
    $response->assertRedirect(); // Should redirect to login
});

test('user cannot access other users 2FA data', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $user2 = User::factory()->create([
        'two_factor_secret' => 'user2-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['user2code1', 'user2code2'],
    ]);

    // User1 should only see their own 2FA status (disabled)
    $response = $testCase->get(route('admin.security.show'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('twoFactorEnabled', false)
        );
});

test('2FA status page shows correct state for enabled user', function () {
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

test('2FA status page shows correct state for disabled user', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->get(route('admin.security.show'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/account/security')
                ->where('twoFactorEnabled', false)
        );
});

test('user can disable two factor authentication', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable and confirm 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $user = Auth::user();

    $validCode = app(Totp::class)->getCurrentCode($user->two_factor_secret);
    $testCase->post(route('admin.two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $response = $testCase->delete(route('admin.two-factor.destroy'));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasTwoFactorEnabled())->toBeFalse();
});

test('user cannot disable two factor authentication when not enabled', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->delete(route('admin.two-factor.destroy'));

    $response->assertStatus(403);
});

test('user can regenerate recovery codes', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable and confirm 2FA first
    $testCase->post(route('admin.two-factor.store'));

    $user = Auth::user();

    $validCode = app(Totp::class)->getCurrentCode($user->two_factor_secret);
    $testCase->post(route('admin.two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $originalCodes = $user->refresh()->two_factor_recovery_codes;

    $response = $testCase->post(route('admin.two-factor.recovery-codes.store'));

    $response->assertRedirectBack();

    $newCodes = $user->refresh()->two_factor_recovery_codes;

    expect($newCodes)->not->toBe($originalCodes)
        ->and(count($newCodes))->toBe(8);

    foreach ($newCodes as $code) {
        expect($code)->toBeString()
            ->and(mb_strlen($code))->toBe(21)
            ->and($originalCodes)->not->toContain($code);
    }
});

test('user cannot regenerate recovery codes when 2FA not enabled', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->post(route('admin.two-factor.recovery-codes.store'));

    $response->assertStatus(403);
});

test('disabling 2FA clears all related data', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    // Enable and confirm 2FA
    $testCase->post(route('admin.two-factor.store'));

    $user = Auth::user();

    $validCode = app(Totp::class)->getCurrentCode($user->two_factor_secret);
    $testCase->post(route('admin.two-factor.confirm'), [
        'code' => $validCode,
    ]);

    // Verify 2FA is enabled with all data
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->not->toBeNull();

    // Disable 2FA
    $testCase->delete(route('admin.two-factor.destroy'));

    // Verify all data is cleared
    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasTwoFactorEnabled())->toBeFalse()
        ->and($user->hasPendingTwoFactorConfirmation())->toBeFalse();
});

test('requires authentication', function () {
    $response = get(route('admin.security.show'));
    $response->assertRedirect(route('admin.login'));

    $response = post(route('admin.two-factor.store'));
    $response->assertRedirect(route('admin.login'));

    $response = delete(route('admin.two-factor.destroy'));
    $response->assertRedirect(route('admin.login'));

    $response = post(route('admin.two-factor.recovery-codes.store'));
    $response->assertRedirect(route('admin.login'));
});
