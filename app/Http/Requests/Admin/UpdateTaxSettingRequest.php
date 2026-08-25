<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateSettingsInput;
use App\Enums\DisplayTaxTotals;
use App\Enums\TaxBasedOn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateTaxSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'tax_based_on' => ['sometimes', 'required', 'string', Rule::enum(TaxBasedOn::class)],
            'prices_include_tax' => ['sometimes', 'required', 'boolean'],
            'shipping_is_taxable' => ['sometimes', 'required', 'boolean'],
            'display_tax_totals' => ['sometimes', 'required', 'string', Rule::enum(DisplayTaxTotals::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'default_tax_rate' => mb_strtolower(__('Default tax rate')),
            'tax_based_on' => mb_strtolower(__('Tax based on')),
            'prices_include_tax' => mb_strtolower(__('Prices include tax')),
            'shipping_is_taxable' => mb_strtolower(__('Shipping is taxable')),
            'display_tax_totals' => mb_strtolower(__('Display tax totals')),
        ];
    }

    public function toDto(): UpdateSettingsInput
    {
        return UpdateSettingsInput::fromArray($this->validated());
    }
}
