<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\PasskeyLoginController;
use App\Http\Requests\Admin\PasskeyLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

use function Pest\Laravel\post;

covers([
    PasskeyLoginController::class,
    PasskeyLoginRequest::class,
]);

uses()->group('auth', 'passkeys');

function loginPasskey(User $user): Passkey
{
    return $user->passkeys()->create([
        'name' => 'Test Key',
        'credential_id' => Base64UrlSafe::encodeUnpadded('login-credential'),
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);
}

function passkeyVerificationOptions(): string
{
    return WebAuthn::toJson(app(GenerateVerificationOptions::class)());
}

function fakeVerifyPasskey(Passkey $passkey): void
{
    app()->bind(VerifyPasskey::class, fn (): VerifyPasskey => new class($passkey) extends VerifyPasskey
    {
        public function __construct(private readonly Passkey $passkey)
        {
        }

        public function __invoke(
            PublicKeyCredential $credential,
            PublicKeyCredentialRequestOptions $options,
            ?Laravel\Passkeys\Contracts\PasskeyUser $user = null,
        ): Passkey {
            return $this->passkey;
        }
    });
}

test('a verified passkey logs the user in and records the login', function () {
    $user = User::factory()->create(['last_login_at' => null]);
    fakeVerifyPasskey(loginPasskey($user));

    $response = $this
        ->withSession(['passkey.verification_options' => passkeyVerificationOptions()])
        ->postJson(route('admin.passkeys.login.store'), ['credential' => passkeyCredentialPayload()]);

    $response->assertOk()
        ->assertJson(['redirect' => route('admin.dashboard', absolute: false)]);

    expect(Auth::guard('web')->id())->toBe($user->id)
        ->and($user->fresh()->last_login_at)->not->toBeNull()
        ->and($user->fresh()->last_login_ip)->not->toBeNull();
});

test('logging in with a passkey requires a credential', function () {
    post(route('admin.passkeys.login.store'), [])->assertSessionHasErrors('credential');
});

test('logging in with a passkey requires a public key credential type', function () {
    $payload = passkeyCredentialPayload();
    $payload['type'] = 'not-a-public-key';

    post(route('admin.passkeys.login.store'), ['credential' => $payload])
        ->assertSessionHasErrors('credential.type');
});

test('logging in with a passkey fails when the verification session expired', function () {
    $user = User::factory()->create();
    fakeVerifyPasskey(loginPasskey($user));

    $this->postJson(route('admin.passkeys.login.store'), ['credential' => passkeyCredentialPayload()])
        ->assertStatus(422)
        ->assertJsonValidationErrors('credential');

    expect(Auth::guard('web')->check())->toBeFalse();
});
