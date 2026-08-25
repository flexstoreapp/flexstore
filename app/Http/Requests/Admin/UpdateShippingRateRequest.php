<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateShippingRateInput;
use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateShippingRateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'region_id' => ['sometimes', 'required', 'integer', Rule::exists(Region::class, 'id')],
            'shipping_carrier_id' => ['sometimes', 'required', 'integer', Rule::exists(ShippingCarrier::class, 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['required_with:rate', 'string', Rule::enum(ShippingRateType::class)],
            'rate' => ['sometimes', 'required_if:type,flat', 'nullable', 'numeric', 'min:0'],
            'delivery_time' => ['sometimes', 'nullable', 'string', 'max:255'],
            'min_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0', ...($this->filled('min_order_value') ? ['gt:min_order_value'] : [])],
            'min_weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'min_weight_unit' => ['nullable', 'required_with:min_weight', 'string', Rule::enum(WeightUnit::class)],
            'max_weight' => ['sometimes', 'nullable', 'numeric', 'min:0', ...($this->filled('min_weight') ? ['gt:min_weight'] : [])],
            'max_weight_unit' => ['nullable', 'required_with:max_weight', 'string', Rule::enum(WeightUnit::class)],
            'excluded_products' => ['sometimes', 'nullable', 'array'],
            'excluded_products.*' => ['integer', Rule::exists(Product::class, 'id')],
            'excluded_categories' => ['sometimes', 'nullable', 'array'],
            'excluded_categories.*' => ['integer', Rule::exists(Category::class, 'id')],
            'excluded_brands' => ['sometimes', 'nullable', 'array'],
            'excluded_brands.*' => ['integer', Rule::exists(Brand::class, 'id')],
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
            'region_id' => mb_strtolower(__('Region')),
            'shipping_carrier_id' => mb_strtolower(__('Shipping carrier')),
            'name' => mb_strtolower(__('Name')),
            'type' => mb_strtolower(__('Rate type')),
            'rate' => mb_strtolower(__('Rate')),
            'delivery_time' => mb_strtolower(__('Delivery time')),
            'min_order_value' => mb_strtolower(__('Min order value')),
            'max_order_value' => mb_strtolower(__('Max order value')),
            'min_weight' => mb_strtolower(__('Min weight')),
            'min_weight_unit' => mb_strtolower(__('Min weight unit')),
            'max_weight' => mb_strtolower(__('Max weight')),
            'max_weight_unit' => mb_strtolower(__('Max weight unit')),
            'excluded_products' => mb_strtolower(__('Excluded products')),
            'excluded_products.*' => mb_strtolower(__('Excluded products')),
            'excluded_categories' => mb_strtolower(__('Excluded categories')),
            'excluded_categories.*' => mb_strtolower(__('Excluded categories')),
            'excluded_brands' => mb_strtolower(__('Excluded brands')),
            'excluded_brands.*' => mb_strtolower(__('Excluded brands')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    public function toDto(): UpdateShippingRateInput
    {
        return UpdateShippingRateInput::fromArray($this->validated());
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->input('type') === ShippingRateType::Free->value) {
            $this->merge(['rate' => null]);
        }
    }
}
