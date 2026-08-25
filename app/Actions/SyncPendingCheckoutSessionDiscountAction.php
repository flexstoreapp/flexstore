<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CheckoutSessionStatus;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Utilities\OrderUtility;

final readonly class SyncPendingCheckoutSessionDiscountAction
{
    public function __construct(
        private OrderUtility $orderUtility,
    ) {
    }

    public function handle(Cart $cart): void
    {
        $session = CheckoutSession::query()
            ->where('cart_id', $cart->id)
            ->where('status', CheckoutSessionStatus::Pending)
            ->latest()
            ->latest('id')
            ->first();

        if (! $session instanceof CheckoutSession) {
            return;
        }

        $hasCoupon = is_string($cart->coupon_code) && $cart->coupon_code !== '';
        $coupon = $hasCoupon
            ? Coupon::query()->where('code', $cart->coupon_code)->first(['id'])
            : null;
        $discountTotal = $hasCoupon
            ? ($cart->discount_total ?? '0.0000')
            : '0.0000';

        $session->update([
            'coupon_id' => $coupon?->id,
            'coupon_code' => $cart->coupon_code,
            'discount_total' => $discountTotal,
            'total' => $this->orderUtility->calculateOrderTotal(
                $session->subtotal,
                $session->tax_total ?? '0.0000',
                $session->shipping_total ?? '0.0000',
                $discountTotal,
            ),
        ]);
    }
}
