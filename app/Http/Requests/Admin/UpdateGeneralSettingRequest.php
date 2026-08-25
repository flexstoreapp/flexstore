<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateGeneralSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_low_stock_threshold' => ['sometimes', 'required', 'integer', 'min:0'],
            'auto_approve_reviews' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'default_low_stock_threshold' => mb_strtolower(__('Low stock threshold')),
            'auto_approve_reviews' => mb_strtolower(__('Auto-approve reviews')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
