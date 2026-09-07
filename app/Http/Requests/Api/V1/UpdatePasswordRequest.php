<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\UpdateUserInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Override;

final class UpdatePasswordRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'current_password' => mb_strtolower(__('Current password')),
            'password' => mb_strtolower(__('New password')),
        ];
    }

    public function toDto(): UpdateUserInput
    {
        return UpdateUserInput::fromArray($this->safe()->only('password'));
    }
}
