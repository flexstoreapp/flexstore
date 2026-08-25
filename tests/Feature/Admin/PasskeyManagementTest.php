<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\PasskeyController;
use App\Http\Controllers\Admin\PasskeyLoginOptionController;
use App\Http\Controllers\Admin\PasskeyRegistrationOptionController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Requests\Admin\PasskeyLoginRequest;
use App\Http\Requests\Admin\StorePasskeyRequest;
use App\Models\User;
use App\Queries\UserPasskeyListQuery;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers([
    PasskeyController::class,
    SecurityController::class,
    PasskeyRegistrationOptionController::class,
    PasskeyLoginOptionController::class,
    StorePasskeyRequest::class,
    PasskeyLoginRequest::class,
    UserPasskeyListQuery::class,
]);

uses()->group('auth', 'passkeys');

function createPasskey(User $user, string $name = 'Test Key', string $credentialId = 'test-credential-id'): void
{
    $user->passkeys()->create([
        'name' => $name,
        'credential_id' => $credentialId,
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);
}

test('user can fetch passkey registration options', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->get(route('admin.passkeys.options'));

    $response->assertOk()
        ->assertJsonStructure(['options' => ['rp', 'user', 'challenge', 'pubKeyCredParams']]);

    $response->assertSessionHas('passkey.registration_options');
});

test('fetching passkey registration options requires authentication', function () {
    $response = get(route('admin.passkeys.options'));

    $response->assertRedirect(route('admin.login'));
});

test('fetching passkey registration options requires password confirmation', function () {
    $testCase = actingAsSuperAdmin();

    $response = $testCase->get(route('admin.passkeys.options'));

    $response->assertRedirect(route('admin.password.confirm'));
});

test('guest can fetch passkey login options', function () {
    $response = get(route('admin.passkeys.login.options'));

    $response->assertOk()
        ->assertJsonStructure(['options' => ['challenge', 'rpId']]);

    $response->assertSessionHas('passkey.verification_options');
});

test('user can delete their own passkey', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $user = Auth::user();
    createPasskey($user);

    $passkey = $user->passkeys()->firstOrFail();

    $response = $testCase->delete(route('admin.passkeys.destroy', $passkey));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
});

test('user cannot delete another users passkey', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $otherUser = User::factory()->create();
    createPasskey($otherUser, credentialId: 'other-user-credential');

    $passkey = $otherUser->passkeys()->firstOrFail();

    $response = $testCase->delete(route('admin.passkeys.destroy', $passkey));

    $response->assertStatus(403);

    assertDatabaseHas('passkeys', ['id' => $passkey->id]);
});

test('storing a passkey requires a name and credential', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    $response = $testCase->post(route('admin.passkeys.store'), []);

    $response->assertSessionHasErrors(['name', 'credential']);
});

test('the security page includes the users passkeys', function () {
    $testCase = actingAsSuperAdmin();
    $testCase->session(['auth.password_confirmed_at' => time()]);

    createPasskey(Auth::user());

    $response = $testCase->get(route('admin.security.show'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/account/security')
                ->has('passkeys', 1)
                ->where('passkeys.0.name', 'Test Key')
        );
});

test('passkey management endpoints require authentication', function () {
    get(route('admin.passkeys.options'))->assertRedirect(route('admin.login'));
    post(route('admin.passkeys.store'))->assertRedirect(route('admin.login'));
    delete(route('admin.passkeys.destroy', 1))->assertRedirect(route('admin.login'));
});
