<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Coupon;

final readonly class CouponValidationResult
{
    public function __construct(
        public Coupon $coupon,
        public string $discount,
    ) {
    }
}
