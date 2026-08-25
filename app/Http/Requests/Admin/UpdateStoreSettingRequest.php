<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Address\AddressFieldRules;
use App\DTOs\UpdateSettingsInput;
use App\Enums\Country;
use App\Enums\MediaType;
use App\Models\Setting;
use App\Rules\MediaRule;
use App\Utilities\LocalizedText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;
use Propaganistas\LaravelPhone\Rules\Phone;

final class UpdateStoreSettingRequest extends FormRequest
{
    /**
     * @var array<string, string>
     */
    private const array ADDRESS_KEYS = ['city' => 'store_city', 'state' => 'store_state', 'postal_code' => 'store_postal_code'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['sometimes', 'required', 'string', 'max:255'],
            'store_description' => ['sometimes', 'required', 'string', 'max:255'],
            'store_email' => ['sometimes', 'required', 'email'],
            'store_phone' => ['sometimes', 'required', 'string', 'max:20', new Phone],
            'store_street_address' => ['sometimes', 'required', 'string', 'max:255'],
            ...AddressFieldRules::rules($this->addressCountry(), presence: 'sometimes', keys: self::ADDRESS_KEYS),
            'store_country_code' => ['sometimes', 'required', 'string', 'max:2', Rule::in(Country::codes())],
            'selling_countries' => ['sometimes', 'array'],
            'selling_countries.*' => ['string', 'size:2', 'distinct', Rule::in(Country::codes())],
            'store_logo' => ['sometimes', 'nullable', 'integer', new MediaRule(MediaType::Image)],
            'store_logo_dark' => ['sometimes', 'nullable', 'integer', new MediaRule(MediaType::Image)],
            'store_favicon' => ['sometimes', 'nullable', 'integer', new MediaRule(MediaType::Image)],
            'store_social_facebook' => ['sometimes', 'nullable', 'url', 'max:500'],
            'store_social_instagram' => ['sometimes', 'nullable', 'url', 'max:500'],
            'store_social_x' => ['sometimes', 'nullable', 'url', 'max:500'],
            'store_social_tiktok' => ['sometimes', 'nullable', 'url', 'max:500'],
            'store_social_pinterest' => ['sometimes', 'nullable', 'url', 'max:500'],
            'store_social_youtube' => ['sometimes', 'nullable', 'url', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'store_name' => mb_strtolower(__('Store name')),
            'store_description' => mb_strtolower(__('Store description')),
            'store_email' => mb_strtolower(__('Store email')),
            'store_phone' => mb_strtolower(__('Store phone')),
            'store_street_address' => mb_strtolower(__('Street address')),
            ...AddressFieldRules::attributes($this->addressCountry(), keys: self::ADDRESS_KEYS),
            'store_country_code' => mb_strtolower(__('Country')),
            'selling_countries' => mb_strtolower(__('Selling countries')),
            'selling_countries.*' => mb_strtolower(__('Selling country')),
            'store_logo' => mb_strtolower(__('Logo')),
            'store_logo_dark' => mb_strtolower(__('Dark mode logo')),
            'store_favicon' => mb_strtolower(__('Favicon')),
            'store_social_facebook' => mb_strtolower(__('Facebook')),
            'store_social_instagram' => mb_strtolower(__('Instagram')),
            'store_social_x' => mb_strtolower(__('X')),
            'store_social_tiktok' => mb_strtolower(__('TikTok')),
            'store_social_youtube' => mb_strtolower(__('YouTube')),
            'store_social_pinterest' => mb_strtolower(__('Pinterest')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        $values = $this->validated();

        if (array_key_exists('store_description', $values)) {
            $values['store_description'] = LocalizedText::merge(
                Setting::getValue('store_description'),
                $values['store_description'],
            );
        }

        return UpdateSettingsInput::fromArray($values);
    }

    private function addressCountry(): string
    {
        $value = $this->input('store_country_code');

        return is_string($value) ? $value : '';
    }
}
