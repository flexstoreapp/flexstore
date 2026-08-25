<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\DTOs\StoreCartItemInput;
use App\Models\Product;
use App\Rules\ValidProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreCartItemRequest extends FormRequest
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
            'variant_options' => mb_strtolower(__('Variant options')),
            'buy_now' => mb_strtolower(__('Buy now')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists(Product::class, 'id')],
            'product_variant_id' => [
                Rule::requiredIf(fn (): bool => Product::query()
                    ->whereKey($this->integer('product_id'))
                    ->whereHas('variants')
                    ->exists()),
                'nullable',
                'uuid',
                new ValidProductVariant($this->integer('product_id')),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'variant_options' => ['nullable', 'array'],
            'buy_now' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): StoreCartItemInput
    {
        return StoreCartItemInput::fromArray($this->validated());
    }
}
