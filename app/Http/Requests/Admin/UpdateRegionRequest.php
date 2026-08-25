<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateRegionInput;
use App\Enums\Country;
use App\Rules\ValidPostalCodePattern;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateRegionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'countries' => ['sometimes', 'required', 'array', 'min:1', Rule::in(Country::codes())],
            'countries.*' => ['sometimes', 'string', 'distinct', 'max:2'],
            'states' => ['sometimes', 'nullable', 'array'],
            'states.*' => ['sometimes', 'string', 'distinct', 'max:255'],
            'postal_codes' => ['sometimes', 'nullable', 'array'],
            'postal_codes.*' => ['sometimes', 'string', 'distinct', 'max:255', new ValidPostalCodePattern],
            'is_active' => ['sometimes', 'required', 'boolean'],
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

    public function toDto(): UpdateRegionInput
    {
        return UpdateRegionInput::fromArray($this->validated());
    }
}
