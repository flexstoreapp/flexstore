<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class StoreCheckoutCouponRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'coupon_code' => mb_strtolower(__('Coupon code')),
            'customer_email' => mb_strtolower(__('Email address')),
        ];
    }
}
