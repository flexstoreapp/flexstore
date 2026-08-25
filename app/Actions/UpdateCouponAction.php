<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateCouponInput;
use App\Models\Coupon;

final readonly class UpdateCouponAction
{
    public function handle(Coupon $coupon, UpdateCouponInput $input): Coupon
    {
        $map = [
            'code' => $input->code,
            'type' => $input->type,
            'value' => $input->value,
            'min_order_value' => $input->minOrderValue,
            'maximum_discount' => $input->maximumDiscount,
            'usage_limit' => $input->usageLimit,
            'usage_limit_per_customer' => $input->usageLimitPerCustomer,
            'is_active' => $input->isActive,
            'starts_at' => $input->startsAt,
            'expires_at' => $input->expiresAt,
        ];

        $updateData = [];
        foreach ($map as $key => $value) {
            if ($input->has($key)) {
                $updateData[$key] = $value;
            }
        }

        if ($updateData !== []) {
            $coupon->update($updateData);
        }

        return $coupon;
    }
}
