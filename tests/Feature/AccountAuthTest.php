<?php

declare(strict_types=1);

use App\Actions\ResetUserPasswordAction;
use App\Actions\SendPasswordResetLinkAction;
use App\Actions\StoreCustomerAction;
use App\Http\Controllers\Storefront\NewPasswordController;
use App\Http\Controllers\Storefront\PasswordResetLinkController;
use App\Http\Controllers\Storefront\RegisterController;
use App\Http\Controllers\Storefront\SessionController;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Http\Requests\Storefront\LogoutRequest;
use App\Http\Requests\Storefront\ShowLoginRequest;
use App\Http\Requests\Storefront\ShowResetPasswordRequest;
use App\Http\Requests\Storefront\StoreAccountRegistrationRequest;
use App\Http\Requests\Storefront\StoreAccountSessionRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

covers([
    SessionController::class,
    RegisterController::class,
    PasswordResetLinkController::class,
    SendPasswordResetLinkRequest::class,
    NewPasswordController::class,
    ResetPasswordRequest::class,
    StoreAccountSessionRequest::class,
    StoreAccountRegistrationRequest::class,
    ShowResetPasswordRequest::class,
    ShowLoginRequest::class,
    LogoutRequest::class,
    StoreCustomerAction::class,
    SendPasswordResetLinkAction::class,
    ResetUserPasswordAction::class,
]);

uses()->group('auth', 'account');

test('users can authenticate', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    post(route('account.login'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('account.dashboard'));

    assertAuthenticatedAs($user);
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    post(route('account.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    assertGuest();
});

test('authenticated users are redirected from login page', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('account.login'))
        ->assertRedirect(route('account.dashboard'));
});

test('login is rate limited after five failed attempts', function () {
    Event::fake(Lockout::class);

    $user = User::factory()->create(['password' => 'password']);

    for ($i = 0; $i < 5; $i++) {
        post(route('account.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = post(route('account.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    expect(session()->get('errors')->get('email')[0])->toContain('Too many');

    Event::assertDispatched(Lockout::class);

    assertGuest();

    Cache::flush();
});

test('new users can register', function () {
    Event::fake();

    post(route('account.register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertRedirect(route('account.dashboard'));

    assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    Event::assertDispatched(Registered::class);
    assertAuthenticated();
});

test('registration returns the shopper to the page they came from', function () {
    Event::fake();

    withSession(['url.intended' => route('checkout.create')])
        ->post(route('account.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('checkout.create'));
});

test('registration validates required data', function () {
    post(route('account.register'), [
        'name' => '',
        'email' => 'invalid-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['name', 'email', 'password']);
});

test('registration prevents duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    post(route('account.register'), [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

test('users can logout', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.logout'))
        ->assertRedirect('/');

    assertGuest();
});

test('logout redirects to a safe local redirect_to', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.logout', ['redirect_to' => '/checkout']))
        ->assertRedirect('/checkout');

    assertGuest();
});

test('login redirects to a safe redirect_to after authenticating', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    get(route('account.login', ['redirect_to' => '/checkout']))->assertOk();

    post(route('account.login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertRedirect('/checkout');
});

test('rejects an external redirect_to', function () {
    get(route('account.login', ['redirect_to' => 'https://evil.example.com']))
        ->assertSessionHasErrors('redirect_to');
});

test('forgot password page can be rendered', function () {
    get(route('account.password.request'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/account/auth/forgot-password'));
});

test('password reset link can be requested', function () {
    $user = User::factory()->create();

    post(route('account.password.email'), [
        'email' => $user->email,
    ])
        ->assertRedirect()
        ->assertSessionHas('message');
});

test('reset password page can be rendered', function () {
    get(route('account.password.reset', ['token' => 'test-token']) . '?email=test@example.com')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/account/auth/reset-password')
            ->where('token', 'test-token')
            ->where('email', 'test@example.com'));
});

test('the login page accepts a local redirect target', function () {
    get(route('account.login', ['redirect_to' => '/account/orders']))->assertOk();
});

test('the login page rejects an external redirect target', function () {
    get(route('account.login', ['redirect_to' => 'https://evil.test']))
        ->assertSessionHasErrors('redirect_to');
});

test('logging out accepts a local redirect target', function () {
    $customer = User::factory()->create();

    actingAs($customer)
        ->post(route('account.logout'), ['redirect_to' => '/products'])
        ->assertRedirect('/products');

    expect(Auth::check())->toBeFalse();
});

test('logging out rejects an external redirect target', function () {
    actingAs(User::factory()->create())
        ->post(route('account.logout'), ['redirect_to' => 'https://evil.test'])
        ->assertSessionHasErrors('redirect_to');
});
