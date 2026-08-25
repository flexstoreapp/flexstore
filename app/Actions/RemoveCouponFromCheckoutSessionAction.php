<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;
use App\Utilities\OrderUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RemoveCouponFromCheckoutSessionAction
{
    public function __construct(
        private OrderUtility $orderUtility,
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
    ) {
    }

    public function handle(CheckoutSession $session): CheckoutSession
    {
        if ($session->status !== CheckoutSessionStatus::Pending) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('This coupon cannot be removed from the checkout.')],
            ]);
        }

        return DB::transaction(function () use ($session): CheckoutSession {
            $session->update([
                'coupon_id' => null,
                'coupon_code' => null,
                'discount_total' => '0.0000',
                'total' => $this->orderUtility->calculateOrderTotal(
                    $session->subtotal,
                    $session->tax_total ?? '0.0000',
                    $session->shipping_total ?? '0.0000',
                    '0.0000',
                ),
            ]);

            $cart = $session->cart;

            if ($cart !== null) {
                $cart->update([
                    'coupon_code' => null,
                    'discount_total' => '0.0000',
                ]);
                $this->recalculateCartTotalsAction->handle($cart);
            }

            return $session->refresh();
        });
    }
}
