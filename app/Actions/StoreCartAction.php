<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;

final readonly class StoreCartAction
{
    public function handle(?string $cartId = null, ?User $customer = null): Cart
    {
        $cart = Cart::query()->create([
            'id' => $cartId,
            'customer_id' => $customer?->id,
            'subtotal' => '0.0000',
            'discount_total' => '0.0000',
            'shipping_total' => '0.0000',
            'tax_total' => '0.0000',
            'total' => '0.0000',
        ]);

        $cart->setRelation('items', $cart->items()->getModel()->newCollection());

        return $cart;
    }
}
