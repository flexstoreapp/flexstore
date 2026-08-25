<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DestroyCartItemAction
{
    public function __construct(
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private ResolveCartAction $resolveCartAction,
    ) {
    }

    public function handle(?string $cartId, int $cartItemId, ?User $user = null): Cart
    {
        return DB::transaction(function () use ($cartId, $cartItemId, $user): Cart {
            $cart = $this->resolveCartAction->handle($cartId, $user);

            $cartItem = CartItem::query()->findOrFail($cartItemId);

            if ($cart->id !== $cartItem->cart_id) {
                throw ValidationException::withMessages([
                    'item' => __('Cart item does not belong to this cart.'),
                ]);
            }

            $cartItem->delete();

            return $this->recalculateCartTotalsAction->handle($cart);
        });
    }
}
