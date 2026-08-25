<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateTaxRateInput;
use App\Enums\TaxCategory;
use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateTaxRateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'tax_category' => ['sometimes', 'nullable', Rule::enum(TaxCategory::class)],
            'region_id' => ['sometimes', 'required', Rule::exists(Region::class, 'id')],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'min_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0', ...($this->filled('min_order_value') ? ['gt:min_order_value'] : [])],
            'is_compound' => ['sometimes', 'required', 'boolean'],
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
            'name' => mb_strtolower(__('Tax name')),
            'tax_category' => mb_strtolower(__('Tax category')),
            'region_id' => mb_strtolower(__('Region')),
            'rate' => mb_strtolower(__('Tax rate')),
            'priority' => mb_strtolower(__('Priority')),
            'is_compound' => mb_strtolower(__('Compound')),
            'is_active' => mb_strtolower(__('Active')),
            'min_order_value' => mb_strtolower(__('Min order value')),
            'max_order_value' => mb_strtolower(__('Max order value')),
        ];
    }

    public function toDto(): UpdateTaxRateInput
    {
        return UpdateTaxRateInput::fromArray($this->validated());
    }
}
