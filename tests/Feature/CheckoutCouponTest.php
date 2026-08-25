<?php

declare(strict_types=1);

use App\Actions\ApplyCouponToCartAction;
use App\Actions\RemoveCouponFromCartAction;
use App\Http\Controllers\Storefront\CheckoutCouponController;
use App\Http\Requests\Storefront\StoreCheckoutCouponRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;

use function Pest\Laravel\withUnencryptedCookie;

covers([
    CheckoutCouponController::class,
    StoreCheckoutCouponRequest::class,
    ApplyCouponToCartAction::class,
    RemoveCouponFromCartAction::class,
]);

uses()->group('checkout');

test('applies coupon to cart', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
    ]);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $coupon = Coupon::factory()->active()->valid()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.coupons.store'), [
        'coupon_code' => $coupon->code,
        'customer_email' => 'customer@example.com',
    ])->assertRedirect();

    expect($cart->refresh()->coupon_code)->toBe($coupon->code);
});

test('requires a coupon code when applying coupon', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.coupons.store'), [])
        ->assertInvalid('coupon_code')
        ->assertValid('customer_email');
});

test('validates email format when applying coupon', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.coupons.store'), [
        'coupon_code' => 'SAVE10',
        'customer_email' => 'invalid-email',
    ])->assertInvalid('customer_email');
});

test('returns error when coupon is invalid', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.coupons.store'), [
        'coupon_code' => 'INVALID',
        'customer_email' => 'customer@example.com',
    ])->assertInvalid('coupon_code');
});

test('removes coupon from cart', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'coupon_code' => 'SAVE10',
        'discount_total' => '10.0000',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)->delete(route('checkout.coupons.destroy'))->assertRedirect();

    expect($cart->refresh()->coupon_code)->toBeNull();
});
