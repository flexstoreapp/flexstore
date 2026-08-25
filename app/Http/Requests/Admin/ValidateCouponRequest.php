<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class ValidateCouponRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'customer_email' => ['required', 'email'],
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
            'subtotal' => mb_strtolower(__('Subtotal')),
            'customer_email' => mb_strtolower(__('Email address')),
        ];
    }
}
