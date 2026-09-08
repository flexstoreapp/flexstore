<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\ValidateCouponRequest;
use App\Http\Resources\Api\V1\Admin\CouponValidationResource;
use App\Utilities\CouponValidator;

final readonly class CouponValidationController
{
    public function __invoke(ValidateCouponRequest $request, CouponValidator $couponValidator): CouponValidationResource
    {
        return new CouponValidationResource($couponValidator->validate(
            $request->safe()->string('coupon_code')->value(),
            $request->safe()->string('subtotal')->value(),
            $request->safe()->string('customer_email')->value(),
        ));
    }
}
