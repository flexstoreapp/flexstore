<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\DTOs\CheckoutOptionsInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreCheckoutOptionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shipping_rate_id' => ['nullable', 'integer', Rule::exists('shipping_rates', 'id')->where('is_active', true)],
            'shipping_quote_reference' => ['nullable', 'string', 'max:255'],
            'payment_gateway_id' => ['nullable', 'integer', Rule::exists('payment_gateways', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'shipping_rate_id' => mb_strtolower(__('Shipping method')),
            'shipping_quote_reference' => mb_strtolower(__('Shipping quote')),
            'payment_gateway_id' => mb_strtolower(__('Payment method')),
        ];
    }

    public function toDto(): CheckoutOptionsInput
    {
        return CheckoutOptionsInput::fromArray(
            $this->safe()->only(['shipping_rate_id', 'shipping_quote_reference', 'payment_gateway_id']),
        );
    }
}
