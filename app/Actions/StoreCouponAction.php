<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreCouponInput;
use App\Models\Coupon;

final readonly class StoreCouponAction
{
    public function handle(StoreCouponInput $input): Coupon
    {
        return Coupon::query()->create([
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
        ]);
    }
}
