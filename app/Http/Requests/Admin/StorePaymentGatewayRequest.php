<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StorePaymentGatewayInput;
use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StorePaymentGatewayRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', Rule::enum(PaymentGatewayDriver::class)],
            'credentials' => ['nullable', 'array'],
            ...$this->getStripeCredentialsRules(),
            ...$this->getPaypalCredentialsRules(),
            ...$this->getRazorpayCredentialsRules(),
            ...$this->getMollieCredentialsRules(),
            ...$this->getTapCredentialsRules(),
            ...$this->getPaystackCredentialsRules(),
            ...$this->getMercadoPagoCredentialsRules(),
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_order_value' => ['nullable', 'numeric', 'min:0', ...($this->filled('min_order_value') ? ['gt:min_order_value'] : [])],
            'min_weight' => ['nullable', 'numeric', 'min:0'],
            'min_weight_unit' => ['nullable', 'required_with:min_weight', 'string', Rule::enum(WeightUnit::class)],
            'max_weight' => ['nullable', 'numeric', 'min:0', ...($this->filled('min_weight') ? ['gt:min_weight'] : [])],
            'max_weight_unit' => ['nullable', 'required_with:max_weight', 'string', Rule::enum(WeightUnit::class)],
            'excluded_products' => ['nullable', 'array'],
            'excluded_products.*' => ['distinct', Rule::exists(Product::class, 'id')],
            'excluded_categories' => ['nullable', 'array'],
            'excluded_categories.*' => ['distinct', Rule::exists(Category::class, 'id')],
            'excluded_brands' => ['nullable', 'array'],
            'excluded_brands.*' => ['distinct', Rule::exists(Brand::class, 'id')],
            'allowed_regions' => ['nullable', 'array'],
            'allowed_regions.*' => ['distinct', Rule::exists(Region::class, 'id')],
            'supported_currencies' => ['nullable', 'array'],
            'supported_currencies.*' => ['distinct', 'string', 'size:3'],
            'sync_external_refunds' => ['sometimes', 'required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => mb_strtolower(__('Name')),
            'driver' => mb_strtolower(__('Driver')),
            'credentials' => mb_strtolower(__('Credentials')),
            'credentials.publishable_key' => mb_strtolower(__('Publishable key')),
            'credentials.secret_key' => mb_strtolower(__('Secret key')),
            'credentials.signing_secret' => mb_strtolower(__('Signing secret')),
            'credentials.checkout_mode' => mb_strtolower(__('Checkout mode')),
            'credentials.client_id' => mb_strtolower(__('Client ID')),
            'credentials.client_secret' => mb_strtolower(__('Client secret')),
            'credentials.webhook_id' => mb_strtolower(__('Webhook ID')),
            'credentials.sandbox' => mb_strtolower(__('Sandbox')),
            'credentials.key_id' => mb_strtolower(__('Key ID')),
            'credentials.key_secret' => mb_strtolower(__('Key secret')),
            'credentials.webhook_secret' => mb_strtolower(__('Webhook secret')),
            'credentials.api_key' => mb_strtolower(__('API key')),
            'credentials.profile_id' => mb_strtolower(__('Profile ID')),
            'credentials.public_key' => mb_strtolower(__('Public key')),
            'credentials.access_token' => mb_strtolower(__('Access token')),
            'min_order_value' => mb_strtolower(__('Min order value')),
            'max_order_value' => mb_strtolower(__('Max order value')),
            'min_weight' => mb_strtolower(__('Min weight')),
            'min_weight_unit' => mb_strtolower(__('Min weight unit')),
            'max_weight' => mb_strtolower(__('Max weight')),
            'max_weight_unit' => mb_strtolower(__('Max weight unit')),
            'excluded_products' => mb_strtolower(__('Excluded products')),
            'excluded_products.*' => mb_strtolower(__('Excluded products')),
            'excluded_categories' => mb_strtolower(__('Excluded categories')),
            'excluded_categories.*' => mb_strtolower(__('Excluded categories')),
            'excluded_brands' => mb_strtolower(__('Excluded brands')),
            'excluded_brands.*' => mb_strtolower(__('Excluded brands')),
            'allowed_regions' => mb_strtolower(__('Allowed regions')),
            'allowed_regions.*' => mb_strtolower(__('Allowed regions')),
            'supported_currencies' => mb_strtolower(__('Supported currencies')),
            'supported_currencies.*' => mb_strtolower(__('Supported currencies')),
            'sync_external_refunds' => mb_strtolower(__('Sync external refunds')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    public function toDto(): StorePaymentGatewayInput
    {
        return StorePaymentGatewayInput::fromArray($this->validated());
    }

    /**
     * @return array<string, mixed>
     */
    private function getStripeCredentialsRules(): array
    {
        if ($this->input('driver') !== PaymentGatewayDriver::Stripe->value) {
            return [];
        }

        return [
            'credentials.publishable_key' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.secret_key' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.signing_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.checkout_mode' => ['sometimes', 'nullable', 'string', Rule::enum(CheckoutMode::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPaypalCredentialsRules(): array
    {
        if ($this->input('driver') !== PaymentGatewayDriver::Paypal->value) {
            return [];
        }

        return [
            'credentials.client_id' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.client_secret' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.webhook_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.checkout_mode' => ['sometimes', 'nullable', 'string', Rule::enum(CheckoutMode::class)],
            'credentials.sandbox' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRazorpayCredentialsRules(): array
    {
        if ($this->input('driver') !== PaymentGatewayDriver::Razorpay->value) {
            return [];
        }

        return [
            'credentials.key_id' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.key_secret' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.webhook_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.checkout_mode' => ['sometimes', 'nullable', 'string', Rule::enum(CheckoutMode::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMollieCredentialsRules(): array
    {
        if ($this->input('driver') !== PaymentGatewayDriver::Mollie->value) {
            return [];
        }

        return [
            'credentials.api_key' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.profile_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.checkout_mode' => ['sometimes', 'nullable', 'string', Rule::enum(CheckoutMode::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getTapCredentialsRules(): array
    {
        if ($this->input('driver') !== PaymentGatewayDriver::Tap->value) {
            return [];
        }

        return [
            'credentials.public_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.secret_key' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.checkout_mode' => ['sometimes', 'nullable', 'string', Rule::enum(CheckoutMode::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPaystackCredentialsRules(): array
    {
        if ($this->input('driver') !== PaymentGatewayDriver::Paystack->value) {
            return [];
        }

        return [
            'credentials.public_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.secret_key' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.checkout_mode' => ['sometimes', 'nullable', 'string', Rule::enum(CheckoutMode::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMercadoPagoCredentialsRules(): array
    {
        if ($this->input('driver') !== PaymentGatewayDriver::MercadoPago->value) {
            return [];
        }

        return [
            'credentials.public_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.access_token' => ['sometimes', 'required', 'string', 'max:255'],
            'credentials.webhook_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credentials.checkout_mode' => ['sometimes', 'nullable', 'string', Rule::enum(CheckoutMode::class)],
        ];
    }
}
