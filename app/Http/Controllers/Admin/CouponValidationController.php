<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ValidateCouponRequest;
use App\Utilities\CouponValidator;
use Illuminate\Http\JsonResponse;

final readonly class CouponValidationController
{
    public function __invoke(ValidateCouponRequest $request, CouponValidator $couponValidator): JsonResponse
    {
        $validated = $request->validated();

        $result = $couponValidator->validate(
            $validated['coupon_code'],
            $validated['subtotal'],
            $validated['customer_email']
        );

        return response()->json([
            'coupon' => $result->coupon->only('id', 'code', 'type', 'value'),
            'discount' => $result->discount,
        ]);
    }
}
