<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateStorefrontCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storefront_cart_show_cross_sells' => [
                'sometimes',
                'boolean',
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
            'storefront_cart_show_cross_sells' => mb_strtolower(__('Show cross-sells')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
