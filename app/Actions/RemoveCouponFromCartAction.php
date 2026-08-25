<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RemoveCouponFromCartAction
{
    public function __construct(
        private ResolveCartAction $resolveCartAction,
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private SyncPendingCheckoutSessionDiscountAction $syncPendingCheckoutSessionDiscountAction,
    ) {
    }

    public function handle(?string $cartId, ?User $user = null): Cart
    {
        return DB::transaction(function () use ($cartId, $user): Cart {
            $cart = $this->resolveCartAction->handle($cartId, $user);

            $cart->update([
                'coupon_code' => null,
                'discount_total' => '0.0000',
            ]);

            $cart = $this->recalculateCartTotalsAction->handle($cart);
            $this->syncPendingCheckoutSessionDiscountAction->handle($cart);

            return $cart;
        });
    }
}
