<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Support\WebAuthn;
use Override;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeyLoginRequest extends FormRequest
{
    private PublicKeyCredential $credential;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'credential' => mb_strtolower(__('Passkey')),
            'credential.id' => mb_strtolower(__('Passkey')),
            'credential.rawId' => mb_strtolower(__('Passkey')),
            'credential.type' => mb_strtolower(__('Passkey')),
            'credential.response' => mb_strtolower(__('Passkey')),
            'remember' => mb_strtolower(__('Remember me')),
        ];
    }

    public function credential(): PublicKeyCredential
    {
        return $this->credential;
    }

    /**
     * @throws ValidationException
     */
    public function verificationOptions(): PublicKeyCredentialRequestOptions
    {
        $serialized = $this->session()->pull('passkey.verification_options');

        if (! is_string($serialized) || $serialized === '') {
            throw ValidationException::withMessages([
                'credential' => __('The passkey verification session expired. Please try again.'),
            ]);
        }

        return WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class);
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
