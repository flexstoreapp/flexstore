<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreCustomerInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;

final class StoreCustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::defaults()],
            'add_more' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => mb_strtolower(__('Name')),
            'email' => mb_strtolower(__('Email address')),
            'password' => mb_strtolower(__('Password')),
            'add_more' => mb_strtolower(__('Add more')),
        ];
    }

    public function toDto(): StoreCustomerInput
    {
        return StoreCustomerInput::fromArray($this->validated());
    }
}
