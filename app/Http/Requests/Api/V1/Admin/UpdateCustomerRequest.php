<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\UpdateCustomerInput;
use App\Models\User;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;

final class UpdateCustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('customer')] User $customer): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($customer),
            ],
            'password' => ['sometimes', 'nullable', 'string', Password::defaults()],
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
        ];
    }

    public function toDto(): UpdateCustomerInput
    {
        return UpdateCustomerInput::fromArray($this->validated());
    }
}
