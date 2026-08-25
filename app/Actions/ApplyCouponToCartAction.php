<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;
use App\Utilities\CouponValidator;
use Illuminate\Support\Facades\DB;

final readonly class ApplyCouponToCartAction
{
    public function __construct(
        private ResolveCartAction $resolveCartAction,
        private CouponValidator $couponValidator,
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private SyncPendingCheckoutSessionDiscountAction $syncPendingCheckoutSessionDiscountAction,
    ) {
    }

    public function handle(?string $cartId, string $couponCode, ?string $customerEmail = null, ?User $user = null): Cart
    {
        return DB::transaction(function () use ($cartId, $couponCode, $customerEmail, $user): Cart {
            $cart = $this->resolveCartAction->handle($cartId, $user);
            $result = $this->couponValidator->validate($couponCode, $cart->subtotal, $customerEmail);

            $cart->update([
                'coupon_code' => $result->coupon->code,
                'discount_total' => $result->discount,
            ]);

            $cart = $this->recalculateCartTotalsAction->handle($cart);
            $this->syncPendingCheckoutSessionDiscountAction->handle($cart);

            return $cart;
        });
    }
}
