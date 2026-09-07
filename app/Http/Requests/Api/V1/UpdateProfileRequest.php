<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\UpdateUserInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user())],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => mb_strtolower(__('Full name')),
            'email' => mb_strtolower(__('Email address')),
        ];
    }

    public function toDto(): UpdateUserInput
    {
        return UpdateUserInput::fromArray($this->validated());
    }
}
