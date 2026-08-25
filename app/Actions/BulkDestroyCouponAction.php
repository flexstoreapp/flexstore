<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Coupon;

final readonly class BulkDestroyCouponAction
{
    /**
     * @param  list<int>  $couponIds
     */
    public function handle(array $couponIds): int
    {
        return Coupon::query()->whereIn('id', $couponIds)->delete();
    }
}
