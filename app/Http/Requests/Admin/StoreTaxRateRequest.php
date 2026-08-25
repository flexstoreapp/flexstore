<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreTaxRateInput;
use App\Enums\TaxCategory;
use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreTaxRateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_category' => ['nullable', Rule::enum(TaxCategory::class)],
            'region_id' => ['required', 'integer', Rule::exists(Region::class, 'id')],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'priority' => ['integer', 'min:0', 'max:999'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_order_value' => ['nullable', 'numeric', 'min:0', ...($this->filled('min_order_value') ? ['gt:min_order_value'] : [])],
            'is_compound' => ['required', 'boolean'],
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

    public function toDto(): StoreTaxRateInput
    {
        return StoreTaxRateInput::fromArray($this->validated());
    }
}
