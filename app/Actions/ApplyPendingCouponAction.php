<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;
use App\Utilities\CouponValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApplyPendingCouponAction
{
    public function __construct(
        private CouponValidator $couponValidator,
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private SyncPendingCheckoutSessionDiscountAction $syncPendingCheckoutSessionDiscountAction,
    ) {
    }

    public function handle(Cart $cart, string $couponCode, ?User $user = null): bool
    {
        if ($couponCode === '' || $cart->items->isEmpty() || $cart->coupon_code !== null) {
            return false;
        }

        try {
            $result = $this->couponValidator->validate($couponCode, $cart->subtotal, $user?->email);
        } catch (ValidationException) {
            return false;
        }

        DB::transaction(function () use ($cart, $result): void {
            $cart->update([
                'coupon_code' => $result->coupon->code,
                'discount_total' => $result->discount,
            ]);

            $this->recalculateCartTotalsAction->handle($cart);
            $this->syncPendingCheckoutSessionDiscountAction->handle($cart);
        });

        return true;
    }
}
