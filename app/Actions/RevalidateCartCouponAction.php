<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Utilities\CouponValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RevalidateCartCouponAction
{
    public function __construct(
        private CouponValidator $couponValidator,
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private SyncPendingCheckoutSessionDiscountAction $syncPendingCheckoutSessionDiscountAction,
    ) {
    }

    public function handle(Cart $cart, ?string $customerEmail): bool
    {
        if ($cart->coupon_code === null) {
            return false;
        }

        try {
            $this->couponValidator->validate($cart->coupon_code, $cart->subtotal, $customerEmail);

            return false;
        } catch (ValidationException) {
            DB::transaction(function () use ($cart): void {
                $cart->update([
                    'coupon_code' => null,
                    'discount_total' => '0.0000',
                ]);

                $this->recalculateCartTotalsAction->handle($cart);
                $this->syncPendingCheckoutSessionDiscountAction->handle($cart);
            });

            return true;
        }
    }
}
