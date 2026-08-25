<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;
use App\Utilities\CouponValidator;
use App\Utilities\OrderUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApplyCouponToCheckoutSessionAction
{
    public function __construct(
        private CouponValidator $couponValidator,
        private OrderUtility $orderUtility,
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
    ) {
    }

    public function handle(CheckoutSession $session, string $couponCode): CheckoutSession
    {
        if ($session->status !== CheckoutSessionStatus::Pending) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('This coupon cannot be applied to your order.')],
            ]);
        }

        $result = $this->couponValidator->validate($couponCode, $session->subtotal, $session->customer_email);

        return DB::transaction(function () use ($session, $result): CheckoutSession {
            $session->update([
                'coupon_id' => $result->coupon->id,
                'coupon_code' => $result->coupon->code,
                'discount_total' => $result->discount,
                'total' => $this->orderUtility->calculateOrderTotal(
                    $session->subtotal,
                    $session->tax_total ?? '0.0000',
                    $session->shipping_total ?? '0.0000',
                    $result->discount,
                ),
            ]);

            $cart = $session->cart;

            if ($cart !== null) {
                $cart->update([
                    'coupon_code' => $result->coupon->code,
                    'discount_total' => $result->discount,
                ]);
                $this->recalculateCartTotalsAction->handle($cart);
            }

            return $session->refresh();
        });
    }
}
