<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Address\AddressFieldRules;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class OrderShippingOptionsRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'items' => mb_strtolower(__('Items')),
            'items.*.product_id' => mb_strtolower(__('Product')),
            'items.*.product_variant_id' => mb_strtolower(__('Variant')),
            'items.*.quantity' => mb_strtolower(__('Quantity')),
            'shipping_address' => mb_strtolower(__('Shipping address')),
            'shipping_address.first_name' => mb_strtolower(__('First name')),
            'shipping_address.last_name' => mb_strtolower(__('Last name')),
            'shipping_address.address_line_1' => mb_strtolower(__('Street address')),
            'shipping_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'shipping_address.city' => mb_strtolower(__('City')),
            'shipping_address.state' => mb_strtolower(__('State')),
            'shipping_address.postal_code' => mb_strtolower(__('Postal code')),
            'shipping_address.country_code' => mb_strtolower(__('Country')),
            'shipping_address.phone' => mb_strtolower(__('Phone')),
            'different_billing_address' => mb_strtolower(__('Different billing address')),
            'billing_address' => mb_strtolower(__('Billing address')),
            'billing_address.first_name' => mb_strtolower(__('First name')),
            'billing_address.last_name' => mb_strtolower(__('Last name')),
            'billing_address.address_line_1' => mb_strtolower(__('Street address')),
            'billing_address.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'billing_address.city' => mb_strtolower(__('City')),
            'billing_address.state' => mb_strtolower(__('State')),
            'billing_address.postal_code' => mb_strtolower(__('Postal code')),
            'billing_address.country_code' => mb_strtolower(__('Country')),
            'billing_address.phone' => mb_strtolower(__('Phone')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'items.*.product_variant_id' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.country_code' => ['required', 'string', 'size:2'],
            ...AddressFieldRules::rules($this->addressCountry('shipping_address'), 'shipping_address', 'required', ['state', 'postal_code']),
            'different_billing_address' => ['sometimes', 'boolean'],
            'billing_address' => ['sometimes', 'required_if:different_billing_address,true', 'nullable', 'array'],
            'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2'],
            ...AddressFieldRules::rules($this->addressCountry('billing_address'), 'billing_address', 'required_with:billing_address', ['state', 'postal_code']),
        ];
    }

    private function addressCountry(string $prefix): string
    {
        $value = $this->input("{$prefix}.country_code");

        return is_string($value) ? $value : '';
    }
}
