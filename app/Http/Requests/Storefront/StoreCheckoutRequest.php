<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Address\AddressFieldRules;
use App\DTOs\StoreCheckoutInput;
use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Models\Cart;
use App\Models\PaymentGateway;
use App\Models\ShippingRate;
use App\Rules\SellingCountryRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;
use Propaganistas\LaravelPhone\Rules\Phone;

final class StoreCheckoutRequest extends FormRequest
{
    private ?bool $cartRequiresShipping = null;

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'customer_email' => mb_strtolower(__('Email address')),
            'payment_gateway_id' => mb_strtolower(__('Payment method')),
            'shipping_rate_id' => mb_strtolower(__('Shipping method')),
            'shipping_address' => mb_strtolower(__('Shipping address')),
            'shipping_address.first_name' => mb_strtolower(__('First name')),
            'shipping_address.last_name' => mb_strtolower(__('Last name')),
            'shipping_address.address_line_1' => mb_strtolower(__('Street address')),
            'shipping_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'shipping_address.country_code' => mb_strtolower(__('Country')),
            'shipping_address.phone' => mb_strtolower(__('Phone')),
            ...AddressFieldRules::attributes($this->addressCountry('shipping_address'), 'shipping_address'),
            'save_shipping_address' => mb_strtolower(__('Save shipping address')),
            'different_billing_address' => mb_strtolower(__('Different billing address')),
            'billing_address' => mb_strtolower(__('Billing address')),
            'billing_address.first_name' => mb_strtolower(__('First name')),
            'billing_address.last_name' => mb_strtolower(__('Last name')),
            'billing_address.address_line_1' => mb_strtolower(__('Street address')),
            'billing_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'billing_address.country_code' => mb_strtolower(__('Country')),
            'billing_address.phone' => mb_strtolower(__('Phone')),
            ...AddressFieldRules::attributes($this->addressCountry('billing_address'), 'billing_address'),
            'notes' => mb_strtolower(__('Notes')),
            'coupon_code' => mb_strtolower(__('Coupon code')),
            'card_token' => mb_strtolower(__('Card token')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiresShipping = $this->cartRequiresShipping();

        $shipReq = $requiresShipping ? ['required'] : ['nullable'];
        $billReq = $requiresShipping ? ['required_if:different_billing_address,true', 'nullable'] : ['required'];
        $billArrayReq = $requiresShipping ? ['required_if:different_billing_address,true'] : ['required'];
        $billingPresence = $requiresShipping ? 'required_if:different_billing_address,true' : 'required';

        return [
            'customer_email' => ['required', 'email', 'max:255'],
            'payment_gateway_id' => ['nullable', Rule::exists(PaymentGateway::class, 'id')->where('is_active', true)],
            'shipping_rate_id' => [...$shipReq, Rule::exists(ShippingRate::class, 'id')],
            'shipping_address' => [...$shipReq, 'array'],
            'shipping_address.first_name' => [...$shipReq, 'string', 'max:255'],
            'shipping_address.last_name' => [...$shipReq, 'string', 'max:255'],
            'shipping_address.address_line_1' => [...$shipReq, 'string', 'max:255'],
            'shipping_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_address.country_code' => [...$shipReq, 'string', 'size:2', new SellingCountryRule],
            'shipping_address.phone' => ['nullable', 'string', 'max:20', new Phone],
            ...AddressFieldRules::rules($this->addressCountry('shipping_address'), 'shipping_address', $requiresShipping ? 'required' : 'sometimes'),
            'save_shipping_address' => ['nullable', 'boolean'],
            'different_billing_address' => ['nullable', 'boolean'],
            'billing_address' => [...$billArrayReq, 'array'],
            'billing_address.first_name' => [...$billReq, 'string', 'max:255'],
            'billing_address.last_name' => [...$billReq, 'string', 'max:255'],
            'billing_address.address_line_1' => [...$billReq, 'string', 'max:255'],
            'billing_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_address.country_code' => [...$billReq, 'string', 'size:2', new SellingCountryRule],
            'billing_address.phone' => ['nullable', 'string', 'max:20', new Phone],
            ...AddressFieldRules::rules($this->addressCountry('billing_address'), 'billing_address', $billingPresence),
            'notes' => ['nullable', 'string', 'max:255'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'card_token' => [$this->requiresCardToken() ? 'required' : 'nullable', 'string', 'max:255'],
        ];
    }

    public function toDto(): StoreCheckoutInput
    {
        $currencyCode = $this->attributes->get('active_currency');

        return StoreCheckoutInput::fromArray([
            ...$this->validated(),
            'currency_code' => is_string($currencyCode) ? $currencyCode : null,
        ]);
    }

    private function cartRequiresShipping(): bool
    {
        if ($this->cartRequiresShipping !== null) {
            return $this->cartRequiresShipping;
        }

        $user = $this->user();
        $cart = $user !== null
            ? Cart::query()->with('items.product')->where('customer_id', $user->id)->first()
            : null;

        if (! $cart instanceof Cart) {
            $cartId = $this->cookie('cart_id');
            $cart = is_string($cartId) && $cartId !== ''
                ? Cart::query()->with('items.product')->find($cartId)
                : null;
        }

        return $this->cartRequiresShipping = $cart?->requiresShipping() ?? true;
    }

    private function addressCountry(string $prefix): string
    {
        $value = $this->input("{$prefix}.country_code");

        return is_string($value) ? $value : '';
    }

    private function requiresCardToken(): bool
    {
        $gateway = PaymentGateway::query()->find($this->input('payment_gateway_id'));

        if (! $gateway instanceof PaymentGateway) {
            return false;
        }

        return in_array($gateway->driver, [PaymentGatewayDriver::Mollie, PaymentGatewayDriver::Tap], true)
            && ($gateway->credentials['checkout_mode'] ?? CheckoutMode::Embedded->value) === CheckoutMode::Embedded->value;
    }
}
