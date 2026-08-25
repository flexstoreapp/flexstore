<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdatePolicySettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'refund_policy' => ['sometimes', 'nullable', 'string'],
            'privacy_policy' => ['sometimes', 'nullable', 'string'],
            'terms_of_service' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'refund_policy' => mb_strtolower(__('Refund policy')),
            'privacy_policy' => mb_strtolower(__('Privacy policy')),
            'terms_of_service' => mb_strtolower(__('Terms of service')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
