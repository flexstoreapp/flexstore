<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreCouponInput;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreCouponRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'alpha_num', 'max:50', Rule::unique(Coupon::class)],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'numeric', 'min:0', $this->input('type') === 'percentage' ? 'max:100' : null],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'add_more' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'code' => mb_strtolower(__('Coupon code')),
            'type' => mb_strtolower(__('Type')),
            'value' => mb_strtolower(__('Discount value')),
            'min_order_value' => mb_strtolower(__('Min order value')),
            'maximum_discount' => mb_strtolower(__('Max discount amount')),
            'usage_limit' => mb_strtolower(__('Total usage limit')),
            'usage_limit_per_customer' => mb_strtolower(__('Usage limit per customer')),
            'is_active' => mb_strtolower(__('Active')),
            'starts_at' => mb_strtolower(__('Start date & time')),
            'expires_at' => mb_strtolower(__('Expiry date & time')),
            'add_more' => mb_strtolower(__('Add more')),
        ];
    }

    #[Override]
    public function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? Coupon::normalizeCode($this->string('code')->value()) : null,
        ]);
    }

    public function toDto(): StoreCouponInput
    {
        return StoreCouponInput::fromArray($this->validated());
    }
}
