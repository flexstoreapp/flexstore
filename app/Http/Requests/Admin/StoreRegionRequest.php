<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreRegionInput;
use App\Enums\Country;
use App\Rules\ValidPostalCodePattern;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreRegionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'countries' => ['required', 'array', 'min:1', Rule::in(Country::codes())],
            'countries.*' => ['string', 'distinct', 'max:2'],
            'states' => ['nullable', 'array'],
            'states.*' => ['string', 'distinct', 'max:255'],
            'postal_codes' => ['nullable', 'array'],
            'postal_codes.*' => ['string', 'distinct', 'max:255', new ValidPostalCodePattern],
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
            'countries' => mb_strtolower(__('Countries')),
            'states' => mb_strtolower(__('States')),
            'postal_codes' => mb_strtolower(__('Postal codes')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    public function toDto(): StoreRegionInput
    {
        return StoreRegionInput::fromArray($this->validated());
    }
}
