<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Coupon;
use Illuminate\Support\Facades\DB;

final readonly class DecrementCouponUsageAction
{
    public function handle(Coupon $coupon): Coupon
    {
        return DB::transaction(function () use ($coupon): Coupon {
            $freshCoupon = Coupon::query()->lockForUpdate()->find($coupon->id);

            if (! $freshCoupon) {
                return $coupon;
            }

            if ($freshCoupon->used_count > 0) {
                $freshCoupon->decrement('used_count');
            }

            return $freshCoupon;
        });
    }
}
