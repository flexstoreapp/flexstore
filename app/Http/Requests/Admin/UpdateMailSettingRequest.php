<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateMailSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', 'in:tls,ssl'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email:rfc'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'mail_host' => mb_strtolower(__('SMTP host')),
            'mail_port' => mb_strtolower(__('SMTP port')),
            'mail_encryption' => mb_strtolower(__('Encryption')),
            'mail_username' => mb_strtolower(__('Username')),
            'mail_password' => mb_strtolower(__('Password')),
            'mail_from_address' => mb_strtolower(__('From address')),
            'mail_from_name' => mb_strtolower(__('From name')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
