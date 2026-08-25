<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateUserInput;
use App\Enums\AdminThemeColor;
use App\Enums\Appearance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateAppearanceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'appearance' => ['sometimes', 'required', 'string', Rule::enum(Appearance::class)],
            'admin_theme_color' => ['sometimes', 'required', 'string', Rule::enum(AdminThemeColor::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'appearance' => mb_strtolower(__('Appearance')),
            'admin_theme_color' => mb_strtolower(__('Theme color')),
        ];
    }

    public function toDto(): UpdateUserInput
    {
        return UpdateUserInput::fromArray($this->validated());
    }
}
