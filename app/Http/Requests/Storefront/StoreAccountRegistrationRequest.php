<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\DTOs\StoreCustomerInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;

final class StoreAccountRegistrationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
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
            'name' => mb_strtolower(__('Full name')),
            'email' => mb_strtolower(__('Email address')),
            'password' => mb_strtolower(__('Password')),
        ];
    }

    public function toDto(): StoreCustomerInput
    {
        return StoreCustomerInput::fromArray($this->validated());
    }
}
