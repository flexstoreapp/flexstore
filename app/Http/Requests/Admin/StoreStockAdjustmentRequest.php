<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StockAdjustmentInput;
use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'product_id' => mb_strtolower(__('Product')),
            'product_variant_id' => mb_strtolower(__('Variant')),
            'quantity' => mb_strtolower(__('Quantity')),
            'reason' => mb_strtolower(__('Reason')),
            'notes' => mb_strtolower(__('Notes')),
        ];
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'product_variant_id' => [
                'nullable',
                Rule::exists(ProductVariant::class, 'id')->where('product_id', $this->input('product_id')),
            ],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', Rule::enum(StockMovementReason::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDto(): StockAdjustmentInput
    {
        return StockAdjustmentInput::fromArray($this->validated());
    }
}
