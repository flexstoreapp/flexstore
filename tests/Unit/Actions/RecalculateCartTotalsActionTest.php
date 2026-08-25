<?php

declare(strict_types=1);

use App\Actions\RecalculateCartTotalsAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;

covers(RecalculateCartTotalsAction::class);

uses()->group('actions', 'cart');

test('recalculates cart totals', function () {
    $coupon = Coupon::factory()->active()->fixed(5)->valid()->create();

    $cart = Cart::factory()->create([
        'coupon_code' => $coupon->code,
        'subtotal' => '0.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '10.0000',
        'tax_total' => '0.0000',
        'total' => '0.0000',
    ]);

    CartItem::factory()->for($cart)->create([
        'quantity' => 2,
        'unit_price' => '20.0000',
        'total_price' => '40.0000',
    ]);

    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '15.0000',
        'total_price' => '15.0000',
    ]);

    $action = app(RecalculateCartTotalsAction::class);

    $cart = $action->handle($cart);

    expect($cart->subtotal)->toBe('55.0000')
        ->and($cart->discount_total)->toBe('5.0000')
        ->and($cart->shipping_total)->toBe('10.0000')
        ->and($cart->tax_total)->toBe('0.0000')
        ->and($cart->total)->toBe('60.0000');
});
