<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\PasswordResetLinkInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class SendPasswordResetLinkRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'email' => mb_strtolower(__('Email address')),
        ];
    }

    public function toDto(): PasswordResetLinkInput
    {
        return PasswordResetLinkInput::fromArray($this->validated());
    }
}
