<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateStorefrontHeaderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storefront_header_sticky' => ['sometimes', 'boolean'],
            'storefront_header_browse_categories' => ['sometimes', 'array'],
            'storefront_header_browse_categories.*.category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'storefront_header_browse_categories.*.is_mega_menu' => ['boolean'],
            'storefront_header_browse_categories.*.featured_image_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'storefront_header_browse_categories.*.featured_title' => ['nullable', 'string', 'max:150'],
            'storefront_header_browse_categories.*.featured_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'storefront_header_sticky' => mb_strtolower(__('Sticky header')),
            'storefront_header_browse_categories' => mb_strtolower(__('Browse categories')),
            'storefront_header_browse_categories.*.category_id' => mb_strtolower(__('Category')),
            'storefront_header_browse_categories.*.is_mega_menu' => mb_strtolower(__('Mega menu')),
            'storefront_header_browse_categories.*.featured_image_id' => mb_strtolower(__('Featured image')),
            'storefront_header_browse_categories.*.featured_title' => mb_strtolower(__('Featured title')),
            'storefront_header_browse_categories.*.featured_url' => mb_strtolower(__('Featured URL')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
