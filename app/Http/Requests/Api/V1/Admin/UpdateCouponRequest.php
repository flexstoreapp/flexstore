<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\UpdateCouponInput;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateCouponRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('coupon')] Coupon $coupon): array
    {
        $type = $this->filled('type') ? $this->string('type')->value() : $coupon->type->value;
        $after = $this->filled('starts_at') ? 'starts_at' : $coupon->starts_at?->toDateTimeString();

        return [
            'code' => ['sometimes', 'required', 'alpha_num', 'max:50', Rule::unique(Coupon::class)->ignore($coupon)],
            'type' => ['sometimes', 'required', Rule::enum(CouponType::class)],
            'value' => array_filter(['sometimes', 'required', 'numeric', 'min:0', $type === CouponType::Percentage->value ? 'max:100' : null]),
            'min_order_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximum_discount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => array_filter(['sometimes', 'nullable', 'date', $after !== null ? 'after:' . $after : null]),
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
        ];
    }

    public function toDto(): UpdateCouponInput
    {
        return UpdateCouponInput::fromArray($this->validated());
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge([
                'code' => Coupon::normalizeCode($this->string('code')->value()),
            ]);
        }
    }
}
