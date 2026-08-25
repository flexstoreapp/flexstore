<?php

declare(strict_types=1);

use App\Actions\ApplyCouponToCartAction;
use App\Enums\CouponType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

covers(ApplyCouponToCartAction::class);

uses()->group('actions', 'checkout');

test('applies valid coupon to cart', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
    ]);

    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    Coupon::factory()->active()->valid()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
    ]);

    $action = app(ApplyCouponToCartAction::class);
    $result = $action->handle($cart->id, 'SAVE10', 'customer@example.com');

    expect($result->coupon_code)->toBe('SAVE10')
        ->and($result->discount_total)->not()->toBe('0.0000')
        ->and($result->total)->toBeLessThan($result->subtotal);
});

test('trims coupon code before validation', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    Coupon::factory()->active()->valid()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
    ]);

    $action = app(ApplyCouponToCartAction::class);
    $result = $action->handle($cart->id, '  SAVE10  ', 'customer@example.com');

    expect($result->coupon_code)->toBe('SAVE10');
});

test('throws exception when coupon is invalid', function () {
    $cart = Cart::factory()->create();

    $action = app(ApplyCouponToCartAction::class);

    expect(fn () => $action->handle($cart->id, 'INVALID', 'customer@example.com'))
        ->toThrow(ValidationException::class);
});

test('throws exception when coupon is inactive', function () {
    $cart = Cart::factory()->create();
    $coupon = Coupon::factory()->inactive()->create();

    $action = app(ApplyCouponToCartAction::class);

    expect(fn () => $action->handle($cart->id, $coupon->code, 'customer@example.com'))
        ->toThrow(ValidationException::class);
});

test('throws exception when coupon is expired', function () {
    $cart = Cart::factory()->create();
    $coupon = Coupon::factory()->active()->expired()->create();

    $action = app(ApplyCouponToCartAction::class);

    expect(fn () => $action->handle($cart->id, $coupon->code, 'customer@example.com'))
        ->toThrow(ValidationException::class);
});

test('throws exception when coupon usage limit reached', function () {
    $cart = Cart::factory()->create();
    $coupon = Coupon::factory()->active()->withUsageLimit(limit: 1, perCustomer: 1)->used(1)->create();

    $action = app(ApplyCouponToCartAction::class);

    expect(fn () => $action->handle($cart->id, $coupon->code, 'customer@example.com'))
        ->toThrow(ValidationException::class);
});

test('syncs the coupon onto a pending checkout session', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);
    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    $coupon = Coupon::factory()->valid()->fixed(10)->create();

    app(ApplyCouponToCartAction::class)->handle($cart->id, $coupon->code, 'customer@example.com');

    $session->refresh();

    expect($session->coupon_id)->toBe($coupon->id)
        ->and($session->coupon_code)->toBe($coupon->code)
        ->and($session->discount_total)->toBe('10.0000')
        ->and($session->total)->toBe('90.0000');
});

test('refreshes cart totals after applying coupon', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $coupon = Coupon::factory()->active()->valid()->create([
        'value' => '20.0000',
    ]);

    $action = app(ApplyCouponToCartAction::class);
    $result = $action->handle($cart->id, $coupon->code, 'customer@example.com');

    expect($result->discount_total)->not()->toBe('0.0000')
        ->and($result->total)->toBeLessThan($result->subtotal);
});
