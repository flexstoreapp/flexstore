<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\DTOs\CheckoutOptionsInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateCheckoutDraftRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_email' => ['sometimes', 'required', 'email', 'max:255'],

            'shipping_address' => ['sometimes', 'nullable', 'array'],
            'shipping_address.first_name' => ['nullable', 'string', 'max:255'],
            'shipping_address.last_name' => ['nullable', 'string', 'max:255'],
            'shipping_address.address_line_1' => ['nullable', 'string', 'max:255'],
            'shipping_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['nullable', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:255'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:50'],
            'shipping_address.country_code' => ['nullable', 'string', 'size:2'],
            'shipping_address.phone' => ['nullable', 'string', 'max:20'],

            'billing_address' => ['sometimes', 'nullable', 'array'],
            'billing_address.first_name' => ['nullable', 'string', 'max:255'],
            'billing_address.last_name' => ['nullable', 'string', 'max:255'],
            'billing_address.address_line_1' => ['nullable', 'string', 'max:255'],
            'billing_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['nullable', 'string', 'max:255'],
            'billing_address.state' => ['nullable', 'string', 'max:255'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:50'],
            'billing_address.country_code' => ['nullable', 'string', 'size:2'],
            'billing_address.phone' => ['nullable', 'string', 'max:20'],

            'different_billing_address' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'customer_address_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id')->where('user_id', $this->user()?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'customer_email' => mb_strtolower(__('Email address')),
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
            'different_billing_address' => mb_strtolower(__('Different billing address')),
            'notes' => mb_strtolower(__('Notes')),
            'customer_address_id' => mb_strtolower(__('Saved address')),
        ];
    }

    public function toDto(): CheckoutOptionsInput
    {
        return CheckoutOptionsInput::fromArray($this->safe()->only([
            'customer_email',
            'shipping_address',
            'billing_address',
            'different_billing_address',
            'notes',
            'customer_address_id',
        ]));
    }
}
