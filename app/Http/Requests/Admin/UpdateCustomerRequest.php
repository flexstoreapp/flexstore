<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Address\AddressFieldRules;
use App\DTOs\UpdateCustomerInput;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Rules\SellingCountryRule;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Override;
use Propaganistas\LaravelPhone\Rules\Phone;

final class UpdateCustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('customer')] User $customer): array
    {
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($customer),
            ],
            'password' => ['sometimes', 'nullable', 'string', Password::defaults()],
            'addresses' => ['sometimes', 'array'],
            'addresses.*.id' => ['nullable', 'integer', Rule::exists(CustomerAddress::class, 'id')],
            'addresses.*.first_name' => ['required', 'string', 'max:255'],
            'addresses.*.last_name' => ['required', 'string', 'max:255'],
            'addresses.*.address_line_1' => ['required', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.country_code' => ['required', 'string', 'size:2', new SellingCountryRule],
            'addresses.*.phone' => ['required', 'string', 'max:20', new Phone],
            'addresses.*.is_default' => ['sometimes', 'boolean'],
        ];

        foreach ($this->addressCountries() as $index => $country) {
            $rules = [...$rules, ...AddressFieldRules::rules($country, "addresses.{$index}")];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        $attributes = [
            'name' => mb_strtolower(__('Name')),
            'email' => mb_strtolower(__('Email address')),
            'password' => mb_strtolower(__('Password')),
            'addresses' => mb_strtolower(__('Addresses')),
            'addresses.*.id' => mb_strtolower(__('Address')),
            'addresses.*.first_name' => mb_strtolower(__('First name')),
            'addresses.*.last_name' => mb_strtolower(__('Last name')),
            'addresses.*.address_line_1' => mb_strtolower(__('Street address')),
            'addresses.*.address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'addresses.*.country_code' => mb_strtolower(__('Country')),
            'addresses.*.phone' => mb_strtolower(__('Phone')),
            'addresses.*.is_default' => mb_strtolower(__('Default')),
        ];

        foreach ($this->addressCountries() as $index => $country) {
            $attributes = [...$attributes, ...AddressFieldRules::attributes($country, "addresses.{$index}")];
        }

        return $attributes;
    }

    public function toDto(): UpdateCustomerInput
    {
        return UpdateCustomerInput::fromArray($this->validated());
    }

    /**
     * @return array<int|string, string>
     */
    private function addressCountries(): array
    {
        $addresses = $this->input('addresses');

        if (! is_array($addresses)) {
            return [];
        }

        $countries = [];

        foreach (array_keys($addresses) as $index) {
            $country = $this->input("addresses.{$index}.country_code");
            $countries[$index] = is_string($country) ? $country : '';
        }

        return $countries;
    }
}
