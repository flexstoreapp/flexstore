<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\DTOs\UpdateUserInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateAccountProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user())],
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
