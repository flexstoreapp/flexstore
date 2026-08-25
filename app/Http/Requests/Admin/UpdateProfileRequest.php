<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateUserInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;
use Spatie\Permission\Models\Role;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()),
            ],
            'password' => ['sometimes', 'nullable', 'string', Password::defaults()],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', Rule::exists(Role::class, 'id')],
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
            'roles' => mb_strtolower(__('Roles')),
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
