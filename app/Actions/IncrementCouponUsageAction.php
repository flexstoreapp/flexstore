<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class IncrementCouponUsageAction
{
    public function handle(Coupon $coupon): Coupon
    {
        return DB::transaction(function () use ($coupon): Coupon {
            $fresh = Coupon::query()->lockForUpdate()->findOrFail($coupon->id);

            if ($fresh->usage_limit !== null && $fresh->used_count >= $fresh->usage_limit) {
                throw ValidationException::withMessages([
                    'coupon_code' => [__('This coupon is no longer valid.')],
                ]);
            }

            $fresh->increment('used_count');

            return $fresh;
        });
    }
}
