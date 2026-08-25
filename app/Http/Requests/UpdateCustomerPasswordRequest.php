<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\UpdateUserInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Override;

final class UpdateCustomerPasswordRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
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
        $data = $this->validated();
        // Profile/password requests don't include name/email — preserve from authenticated user
        $user = $this->user();
        if (! isset($data['name']) && $user !== null) {
            $data['name'] = $user->name;
        }
        if (! isset($data['email']) && $user !== null) {
            $data['email'] = $user->email;
        }

        return UpdateUserInput::fromArray($data);
    }
}
