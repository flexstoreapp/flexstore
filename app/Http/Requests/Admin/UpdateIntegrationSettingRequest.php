<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateIntegrationSettingRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'integration_google_analytics_id.regex' => __('The measurement ID must start with G- followed by alphanumeric characters.'),
            'integration_google_tag_manager_id.regex' => __('The container ID must start with GTM- followed by alphanumeric characters.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'integration_google_analytics_id' => mb_strtolower(__('Google Analytics ID')),
            'integration_google_tag_manager_id' => mb_strtolower(__('Google Tag Manager ID')),
            'integration_meta_pixel_id' => mb_strtolower(__('Meta Pixel ID')),
            'integration_tiktok_pixel_id' => mb_strtolower(__('TikTok Pixel ID')),
            'integration_pinterest_tag_id' => mb_strtolower(__('Pinterest Tag ID')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'integration_google_analytics_id' => ['sometimes', 'nullable', 'string', 'regex:/^G-[a-zA-Z0-9]+$/'],
            'integration_google_tag_manager_id' => ['sometimes', 'nullable', 'string', 'regex:/^GTM-[a-zA-Z0-9]+$/'],
            'integration_meta_pixel_id' => ['sometimes', 'nullable', 'numeric'],
            'integration_tiktok_pixel_id' => ['sometimes', 'nullable', 'alpha_num'],
            'integration_pinterest_tag_id' => ['sometimes', 'nullable', 'numeric'],
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
