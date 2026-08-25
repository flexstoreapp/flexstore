<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;

final readonly class ResolveCartAction
{
    public function __construct(
        private StoreCartAction $storeCartAction,
    ) {
    }

    public function handle(?string $cartId = null, ?User $customer = null): Cart
    {
        $cart = $this->findCart($customer, $cartId);

        if ($cart instanceof Cart) {
            if ($customer instanceof User && $cart->customer_id === null) {
                $cart->update(['customer_id' => $customer->id]);
            }

            return $cart;
        }

        return $this->storeCartAction->handle($cartId, $customer);
    }

    private function findCart(?User $customer, ?string $cartId): ?Cart
    {
        if ($customer instanceof User) {
            $cart = Cart::query()
                ->with('items')
                ->where('customer_id', $customer->id)
                ->first();

            if ($cart instanceof Cart) {
                return $cart;
            }
        }

        if (in_array($cartId, [null, '', '0'], true)) {
            return null;
        }

        return Cart::query()
            ->with('items')
            ->whereKey($cartId)
            ->first();
    }
}
