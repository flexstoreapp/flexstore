<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ConfirmablePasswordController;
use App\Http\Controllers\Admin\PasskeyConfirmationController;
use App\Http\Controllers\Admin\PasskeyConfirmationOptionController;
use App\Http\Requests\Admin\ConfirmPasskeyRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers([
    ConfirmablePasswordController::class,
    PasskeyConfirmationController::class,
    PasskeyConfirmationOptionController::class,
    ConfirmPasskeyRequest::class,
]);

uses()->group('auth', 'passkeys');

function addPasskey(User $user): void
{
    $user->passkeys()->create([
        'name' => 'Test Key',
        'credential_id' => Base64UrlSafe::encodeUnpadded('confirm-credential'),
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);
}

test('authenticated user can fetch confirm-password passkey options', function () {
    $testCase = actingAsSuperAdmin();
    addPasskey(Auth::user());

    $response = $testCase->get(route('admin.password.confirm.passkey.options'));

    $response->assertOk()
        ->assertJsonStructure(['options' => ['challenge', 'rpId', 'allowCredentials']]);

    $response->assertSessionHas('passkey.verification_options');
});

test('confirm-password passkey options scope credentials to the current user', function () {
    $testCase = actingAsSuperAdmin();
    addPasskey(Auth::user());

    $response = $testCase->get(route('admin.password.confirm.passkey.options'));

    expect($response->json('options.allowCredentials'))->toHaveCount(1);
});

test('fetching confirm-password passkey options requires authentication', function () {
    get(route('admin.password.confirm.passkey.options'))->assertRedirect(route('admin.login'));
});

test('confirming a passkey requires a credential', function () {
    $testCase = actingAsSuperAdmin();

    $response = $testCase->post(route('admin.password.confirm.passkey.store'), []);

    $response->assertSessionHasErrors('credential');
});

test('confirming a passkey requires authentication', function () {
    post(route('admin.password.confirm.passkey.store'))->assertRedirect(route('admin.login'));
});

test('the confirm-password page renders', function () {
    $testCase = actingAsSuperAdmin();

    $testCase->get(route('admin.password.confirm'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/auth/confirm-password'));
});

test('a verified passkey confirms the password', function () {
    $testCase = actingAsSuperAdmin();
    $user = Auth::user();
    addPasskey($user);

    $passkey = $user->passkeys()->first();

    app()->bind(VerifyPasskey::class, fn (): VerifyPasskey => new class($passkey) extends VerifyPasskey
    {
        public function __construct(private readonly Passkey $passkey)
        {
        }

        public function __invoke(
            PublicKeyCredential $credential,
            PublicKeyCredentialRequestOptions $options,
            ?PasskeyUser $user = null,
        ): Passkey {
            return $this->passkey;
        }
    });

    $response = $testCase
        ->withSession(['passkey.verification_options' => WebAuthn::toJson(app(GenerateVerificationOptions::class)())])
        ->postJson(route('admin.password.confirm.passkey.store'), ['credential' => passkeyCredentialPayload()]);

    $response->assertOk()->assertJsonStructure(['redirect']);

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('confirming a passkey fails when the verification session expired', function () {
    $testCase = actingAsSuperAdmin();
    addPasskey(Auth::user());

    $testCase->postJson(route('admin.password.confirm.passkey.store'), ['credential' => passkeyCredentialPayload()])
        ->assertStatus(422)
        ->assertJsonValidationErrors('credential');
});
