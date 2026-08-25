<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\PasswordResetInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Override;

final class ResetPasswordRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'token' => mb_strtolower(__('Token')),
            'email' => mb_strtolower(__('Email address')),
            'password' => mb_strtolower(__('Password')),
            'password_confirmation' => mb_strtolower(__('Password confirmation')),
        ];
    }

    public function toDto(): PasswordResetInput
    {
        return PasswordResetInput::fromArray($this->validated());
    }
}
