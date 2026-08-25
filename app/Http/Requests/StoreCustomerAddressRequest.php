<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Address\AddressFieldRules;
use App\DTOs\StoreCustomerAddressInput;
use App\Rules\SellingCountryRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;
use Propaganistas\LaravelPhone\Rules\Phone;

final class StoreCustomerAddressRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', new SellingCountryRule],
            'phone' => ['nullable', 'string', 'max:20', new Phone],
            'is_default' => ['sometimes', 'boolean'],
            ...AddressFieldRules::rules($this->addressCountry()),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'first_name' => mb_strtolower(__('First name')),
            'last_name' => mb_strtolower(__('Last name')),
            'address_line_1' => mb_strtolower(__('Street address')),
            'address_line_2' => mb_strtolower(__('Apartment, suite, etc.')),
            'country_code' => mb_strtolower(__('Country')),
            'phone' => mb_strtolower(__('Phone')),
            'is_default' => mb_strtolower(__('Default')),
            ...AddressFieldRules::attributes($this->addressCountry()),
        ];
    }

    public function toDto(): StoreCustomerAddressInput
    {
        return StoreCustomerAddressInput::fromArray($this->validated());
    }

    private function addressCountry(): string
    {
        $value = $this->input('country_code');

        return is_string($value) ? $value : '';
    }
}
