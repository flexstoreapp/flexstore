<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateCheckoutSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_checkout_enabled' => ['sometimes', 'required', 'boolean'],
            'checkout_reservation_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:1440'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'guest_checkout_enabled' => mb_strtolower(__('Guest checkout')),
            'checkout_reservation_minutes' => mb_strtolower(__('Stock reservation window (minutes)')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
