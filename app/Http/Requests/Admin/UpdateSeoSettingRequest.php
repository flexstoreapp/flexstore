<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateSeoSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'seo_homepage_meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo_homepage_meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'seo_shop_meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo_shop_meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'seo_robots_indexing' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'seo_homepage_meta_title' => mb_strtolower(__('SEO title')),
            'seo_homepage_meta_description' => mb_strtolower(__('SEO description')),
            'seo_shop_meta_title' => mb_strtolower(__('SEO title')),
            'seo_shop_meta_description' => mb_strtolower(__('SEO description')),
            'seo_robots_indexing' => mb_strtolower(__('Search engine indexing')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
