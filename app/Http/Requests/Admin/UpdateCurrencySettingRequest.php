<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateCurrencySettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'base_currency' => [
                'required',
                'string',
                Rule::in(Currency::query()->where('is_active', true)->pluck('code')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'base_currency' => mb_strtolower(__('Base currency')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
