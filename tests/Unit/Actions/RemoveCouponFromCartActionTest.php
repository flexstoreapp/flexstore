<?php

declare(strict_types=1);

use App\Actions\RemoveCouponFromCartAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Coupon;

covers(RemoveCouponFromCartAction::class);

uses()->group('actions', 'checkout');

test('removes coupon from cart', function () {
    $cart = Cart::factory()->create([
        'coupon_code' => 'SAVE10',
        'discount_total' => '10.0000',
    ]);

    $action = app(RemoveCouponFromCartAction::class);
    $result = $action->handle($cart->id);

    expect($result->coupon_code)->toBeNull()
        ->and($result->discount_total)->toBe('0.0000');
});

test('resets discount total to zero', function () {
    $cart = Cart::factory()->create([
        'coupon_code' => 'SAVE20',
        'discount_total' => '20.0000',
        'subtotal' => '100.0000',
        'total' => '80.0000',
    ]);

    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $action = app(RemoveCouponFromCartAction::class);
    $result = $action->handle($cart->id);

    expect($result->discount_total)->toBe('0.0000')
        ->and($result->total)->toBe('100.0000');
});

test('clears the coupon on a pending checkout session', function () {
    $coupon = Coupon::factory()->valid()->fixed(10)->create();
    $cart = Cart::factory()->create([
        'coupon_code' => $coupon->code,
        'discount_total' => '10.0000',
        'subtotal' => '100.0000',
        'total' => '90.0000',
    ]);
    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);
    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '10.0000',
        'total' => '90.0000',
    ]);

    app(RemoveCouponFromCartAction::class)->handle($cart->id);

    $session->refresh();

    expect($session->coupon_id)->toBeNull()
        ->and($session->coupon_code)->toBeNull()
        ->and($session->discount_total)->toBe('0.0000')
        ->and($session->total)->toBe('100.0000');
});

test('handles cart without coupon', function () {
    $cart = Cart::factory()->create([
        'coupon_code' => null,
        'discount_total' => '0.0000',
    ]);

    $action = app(RemoveCouponFromCartAction::class);
    $result = $action->handle($cart->id);

    expect($result->coupon_code)->toBeNull()
        ->and($result->discount_total)->toBe('0.0000');
});

test('refreshes cart totals after removing coupon', function () {
    $cart = Cart::factory()->create([
        'coupon_code' => 'SAVE10',
        'subtotal' => '100.0000',
        'discount_total' => '10.0000',
        'shipping_total' => '5.0000',
        'tax_total' => '8.5000',
        'total' => '103.5000',
    ]);

    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $action = app(RemoveCouponFromCartAction::class);
    $result = $action->handle($cart->id);

    expect($result->discount_total)->toBe('0.0000')
        ->and($result->total)->toBe('113.5000');
});
