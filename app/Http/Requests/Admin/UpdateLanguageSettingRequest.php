<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use App\Rules\DefaultInAvailableRule;
use App\Rules\ValidLocale;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateLanguageSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_locale' => ['required', 'string', 'max:10', new ValidLocale, new DefaultInAvailableRule('available_locales')],
            'available_locales' => ['required', 'array', 'min:1'],
            'available_locales.*' => ['required', 'string', 'max:10', new ValidLocale],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'default_locale' => mb_strtolower(__('Default language')),
            'available_locales' => mb_strtolower(__('Available languages')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
