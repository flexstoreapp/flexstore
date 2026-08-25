<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;

final readonly class ClearCartAction
{
    public function handle(Cart $cart): Cart
    {
        $cart->items()->delete();

        $cart->update([
            'shipping_rate_id' => null,
            'payment_gateway_id' => null,
            'coupon_code' => null,
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
