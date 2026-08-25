<?php

declare(strict_types=1);

use App\Actions\RemoveCouponFromCartAction;
use App\Http\Controllers\Storefront\CheckoutCouponController;
use App\Models\Cart;

use function Pest\Laravel\withUnencryptedCookie;

covers(CheckoutCouponController::class, RemoveCouponFromCartAction::class);

uses()->group('checkout');

test('removes coupon from cart', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'coupon_code' => 'SAVE10',
        'discount_total' => '10.0000',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)->delete(route('checkout.coupons.destroy'))->assertRedirect();

    expect($cart->refresh()->coupon_code)->toBeNull();
});
