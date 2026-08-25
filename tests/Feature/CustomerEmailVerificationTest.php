<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\EmailVerificationNoticeController;
use App\Http\Controllers\Storefront\EmailVerificationNotificationController;
use App\Http\Controllers\Storefront\VerifyEmailController;
use App\Models\User;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers([
    EmailVerificationNoticeController::class,
    EmailVerificationNotificationController::class,
    VerifyEmailController::class,
]);

uses()->group('auth', 'account', 'email-verification');

test('verification notice page can be rendered for unverified users', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->get(route('account.verification.notice'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/account/auth/verify-email'));
});

test('verified users are redirected from verification notice page', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('account.verification.notice'))
        ->assertRedirect(route('account.dashboard'));
});

test('guests are redirected to login from verification notice page', function () {
    get(route('account.verification.notice'))
        ->assertRedirect(route('account.login'));
});

test('email can be verified', function () {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'account.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('account.dashboard') . '?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    Event::assertDispatched(Verified::class);
});

test('email verification fails with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'account.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    actingAs($user)
        ->get($verificationUrl)
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification email can be resent', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->post(route('account.verification.send'))
        ->assertRedirect()
        ->assertSessionHas('message');

    Notification::assertSentTo($user, CustomerVerifyEmailNotification::class);
});

test('resend verification is skipped for already verified users', function () {
    Notification::fake();

    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.verification.send'))
        ->assertRedirect(route('account.dashboard'));

    Notification::assertNotSentTo($user, CustomerVerifyEmailNotification::class);
});

test('unverified users can access dashboard with verification banner', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/account/dashboard')
            ->where('auth.user.email_verified_at', null));
});

test('verification email is sent on registration', function () {
    Notification::fake();

    post(route('account.register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    Notification::assertSentTo($user, CustomerVerifyEmailNotification::class);
});

test('expired verification link is rejected', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'account.verification.verify',
        now()->subMinutes(1),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    actingAs($user)
        ->get($verificationUrl)
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('cannot verify another user email', function () {
    $user = User::factory()->unverified()->create();
    $otherUser = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'account.verification.verify',
        now()->addMinutes(60),
        ['id' => $otherUser->id, 'hash' => sha1($otherUser->email)]
    );

    actingAs($user)
        ->get($verificationUrl)
        ->assertForbidden();

    expect($otherUser->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification request is throttled', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user);

    for ($i = 0; $i < 5; $i++) {
        post(route('account.verification.send'))
            ->assertRedirect();
    }

    post(route('account.verification.send'))
        ->assertStatus(429);
});

test('verification link click is throttled', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'account.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    actingAs($user);

    for ($i = 0; $i < 5; $i++) {
        get($verificationUrl)
            ->assertForbidden();
    }

    get($verificationUrl)
        ->assertStatus(429);
});
