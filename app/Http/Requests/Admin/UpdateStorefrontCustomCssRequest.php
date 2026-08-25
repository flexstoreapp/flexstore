<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateStorefrontCustomCssRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storefront_custom_css' => ['nullable', 'string', 'max:50000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'storefront_custom_css' => mb_strtolower(__('Custom CSS')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
