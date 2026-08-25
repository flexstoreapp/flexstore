<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateStorefrontFooterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storefront_footer_show_copyright' => ['sometimes', 'boolean'],
            'storefront_footer_show_social_links' => ['sometimes', 'boolean'],
            'storefront_footer_show_payment_badges' => ['sometimes', 'boolean'],
            'storefront_footer_payment_method_preset' => ['sometimes', 'string', Rule::in(['all', 'credit_cards', 'digital_wallets', 'custom'])],
            'storefront_footer_payment_methods' => ['sometimes', 'array'],
            'storefront_footer_payment_methods.*' => ['string', Rule::in(['visa', 'mastercard', 'amex', 'discover', 'jcb', 'unionpay', 'mada', 'paypal', 'apple_pay', 'google_pay', 'upi', 'ideal'])],
            'storefront_footer_copyright_text' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'storefront_footer_show_copyright' => mb_strtolower(__('Show copyright')),
            'storefront_footer_show_social_links' => mb_strtolower(__('Show social links')),
            'storefront_footer_show_payment_badges' => mb_strtolower(__('Show payment badges')),
            'storefront_footer_payment_method_preset' => mb_strtolower(__('Payment method preset')),
            'storefront_footer_payment_methods' => mb_strtolower(__('Payment methods')),
            'storefront_footer_payment_methods.*' => mb_strtolower(__('Payment method')),
            'storefront_footer_copyright_text' => mb_strtolower(__('Copyright text')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
