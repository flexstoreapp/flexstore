<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Admin\AdminAccessTokenController;
use App\Http\Controllers\Api\V1\Admin\AdminProfileController;
use App\Http\Controllers\Api\V1\Admin\AdminTwoFactorChallengeController;
use App\Http\Requests\Api\V1\Admin\StoreAdminAccessTokenRequest;
use App\Http\Requests\Api\V1\Admin\StoreTwoFactorChallengeRequest;
use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Models\User;
use App\TwoFactor\Totp;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(
    AdminAccessTokenController::class,
    AdminProfileController::class,
    AdminTwoFactorChallengeController::class,
    StoreAdminAccessTokenRequest::class,
    StoreTwoFactorChallengeRequest::class,
    AdminUserResource::class,
);

uses()->group('api', 'admin-api');

test('a staff member exchanges credentials for an admin token', function (): void {
    $user = userWithPermissions([Permission::DashboardView]);
    $user->forceFill(['password' => 'password'])->save();

    $response = postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email', 'roles', 'permissions']]);

    expect($user->tokens()->sole()->abilities)->toBe([TokenAbility::Admin->value])
        ->and($response->json('user.permissions'))->toContain(Permission::DashboardView->value);
});

test('a customer cannot get an admin token', function (): void {
    $customer = User::factory()->create(['password' => 'password']);

    postJson(route('api.v1.admin.auth.login'), [
        'email' => $customer->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    expect($customer->tokens()->count())->toBe(0);
});

test('a wrong password is rejected', function (): void {
    $user = userWithPermissions([Permission::DashboardView]);

    postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'not-the-password',
        'device_name' => 'Studio display',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('the profile endpoint returns the staff member behind the token', function (): void {
    $user = actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    getJson(route('api.v1.admin.auth.me'))
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('permissions', [Permission::OrdersView->value]);
});

test('a customer token cannot reach the admin api', function (): void {
    actingAsApiCustomer();

    getJson(route('api.v1.admin.auth.me'))->assertForbidden();
});

test('the token is revoked on logout', function (): void {
    $user = actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    postJson(route('api.v1.admin.auth.logout'))->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

test('two factor login returns a pending token rather than an admin one', function (): void {
    $user = userWithPermissions([Permission::DashboardView]);
    $user->forceFill([
        'password' => 'password',
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])
        ->assertOk()
        ->assertJsonPath('two_factor_required', true)
        ->assertJsonStructure(['two_factor_token', 'expires_in'])
        ->assertJsonMissingPath('token');

    expect($user->tokens()->sole()->abilities)->toBe([TokenAbility::TwoFactorPending->value]);

    $pending = $response->json('two_factor_token');

    getJson(route('api.v1.admin.auth.me'), ['Authorization' => 'Bearer ' . $pending])->assertForbidden();
});

test('the pending token is exchanged for an admin token with a valid code', function (): void {
    $user = userWithPermissions([Permission::DashboardView]);
    $user->forceFill([
        'password' => 'password',
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $pending = postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])->json('two_factor_token');

    $token = postJson(route('api.v1.admin.auth.two-factor'), [
        'code' => app(Totp::class)->getCurrentCode($user->refresh()->two_factor_secret),
        'device_name' => 'Studio display',
    ], ['Authorization' => 'Bearer ' . $pending])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'permissions']])
        ->json('token');

    expect($user->tokens()->sole()->abilities)->toBe([TokenAbility::Admin->value]);

    // The guard caches the token it resolved for the previous call in this test.
    $this->app['auth']->forgetGuards();

    getJson(route('api.v1.admin.auth.me'), ['Authorization' => 'Bearer ' . $token])
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

test('a wrong code leaves the pending token in place', function (): void {
    $user = userWithPermissions([Permission::DashboardView]);
    $user->forceFill([
        'password' => 'password',
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $pending = postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])->json('two_factor_token');

    postJson(route('api.v1.admin.auth.two-factor'), [
        'code' => '000000',
        'device_name' => 'Studio display',
    ], ['Authorization' => 'Bearer ' . $pending])->assertUnprocessable();

    expect($user->tokens()->sole()->abilities)->toBe([TokenAbility::TwoFactorPending->value]);
});

test('the challenge needs a code or a recovery code', function (): void {
    $user = userWithPermissions([Permission::DashboardView]);
    $user->forceFill([
        'password' => 'password',
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $pending = postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])->json('two_factor_token');

    postJson(route('api.v1.admin.auth.two-factor'), ['device_name' => 'Studio display'], [
        'Authorization' => 'Bearer ' . $pending,
    ])->assertUnprocessable()->assertJsonValidationErrors(['code', 'recovery_code']);
});

test('an admin token cannot be used on the challenge endpoint', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    postJson(route('api.v1.admin.auth.two-factor'), ['code' => '123456', 'device_name' => 'Studio display'])
        ->assertForbidden();
});

test('a super admin can log in even though the role carries no permission rows', function (): void {
    $user = User::factory()->create(['password' => 'password'])
        ->assignRole(Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]));

    postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])->assertOk()->assertJsonStructure(['token']);
});

test('the challenge is not found when two factor was turned off while it was pending', function (): void {
    $user = userWithPermissions([Permission::DashboardView]);
    $user->forceFill([
        'password' => 'password',
        'two_factor_secret' => app(Totp::class)->generateSecret(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $pending = postJson(route('api.v1.admin.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Studio display',
    ])->json('two_factor_token');

    $code = app(Totp::class)->getCurrentCode($user->refresh()->two_factor_secret);
    $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();

    postJson(route('api.v1.admin.auth.two-factor'), [
        'code' => $code,
        'device_name' => 'Studio display',
    ], ['Authorization' => 'Bearer ' . $pending])->assertNotFound();
});
