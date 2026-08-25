<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PaymentGatewayDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class TestPaymentGatewayConnectionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'driver' => ['required', Rule::enum(PaymentGatewayDriver::class)],
            'credentials' => ['required', 'array'],
            ...$this->credentialRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'driver' => mb_strtolower(__('Driver')),
            'credentials' => mb_strtolower(__('Credentials')),
            'credentials.secret_key' => mb_strtolower(__('Secret key')),
            'credentials.client_id' => mb_strtolower(__('Client ID')),
            'credentials.client_secret' => mb_strtolower(__('Client secret')),
            'credentials.sandbox' => mb_strtolower(__('Sandbox')),
            'credentials.key_id' => mb_strtolower(__('Key ID')),
            'credentials.key_secret' => mb_strtolower(__('Key secret')),
            'credentials.api_key' => mb_strtolower(__('API key')),
            'credentials.access_token' => mb_strtolower(__('Access token')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function credentialRules(): array
    {
        return match ($this->input('driver')) {
            PaymentGatewayDriver::Stripe->value => [
                'credentials.secret_key' => ['required', 'string'],
            ],
            PaymentGatewayDriver::Paypal->value => [
                'credentials.client_id' => ['required', 'string'],
                'credentials.client_secret' => ['required', 'string'],
                'credentials.sandbox' => ['sometimes', 'boolean'],
            ],
            PaymentGatewayDriver::Razorpay->value => [
                'credentials.key_id' => ['required', 'string'],
                'credentials.key_secret' => ['required', 'string'],
            ],
            PaymentGatewayDriver::Mollie->value => [
                'credentials.api_key' => ['required', 'string'],
            ],
            PaymentGatewayDriver::Tap->value => [
                'credentials.secret_key' => ['required', 'string'],
            ],
            PaymentGatewayDriver::Paystack->value => [
                'credentials.secret_key' => ['required', 'string'],
            ],
            PaymentGatewayDriver::MercadoPago->value => [
                'credentials.access_token' => ['required', 'string'],
            ],
            default => [],
        };
    }
}
