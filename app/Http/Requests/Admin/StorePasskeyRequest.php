<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Support\WebAuthn;
use Override;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

final class StorePasskeyRequest extends FormRequest
{
    private PublicKeyCredential $credential;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => mb_strtolower(__('Passkey name')),
            'credential' => mb_strtolower(__('Passkey')),
            'credential.id' => mb_strtolower(__('Passkey')),
            'credential.rawId' => mb_strtolower(__('Passkey')),
            'credential.type' => mb_strtolower(__('Passkey')),
            'credential.response' => mb_strtolower(__('Passkey')),
        ];
    }

    public function credential(): PublicKeyCredential
    {
        return $this->credential;
    }

    /**
     * @throws ValidationException
     */
    public function registrationOptions(): PublicKeyCredentialCreationOptions
    {
        $serialized = $this->session()->pull('passkey.registration_options');

        if (! is_string($serialized) || $serialized === '') {
            throw ValidationException::withMessages([
                'credential' => __('The passkey registration session expired. Please try again.'),
            ]);
        }

        return WebAuthn::fromJson($serialized, PublicKeyCredentialCreationOptions::class);
    }

    /**
     * @throws ValidationException
     */
    #[Override]
    protected function passedValidation(): void
    {
        try {
            $this->credential = WebAuthn::fromJson(
                json_encode($this->input('credential')) ?: '{}',
                PublicKeyCredential::class,
            );
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'credential' => __('The passkey could not be verified. Please try again.'),
            ]);
        }
    }
}
