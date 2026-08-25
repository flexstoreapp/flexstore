<?php

declare(strict_types=1);

use App\Actions\SyncPendingCheckoutSessionDiscountAction;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\Coupon;

covers(SyncPendingCheckoutSessionDiscountAction::class);

uses()->group('actions', 'abandoned-checkout');

test('does nothing when no pending session exists', function () {
    $cart = Cart::factory()->create([
        'coupon_code' => 'SAVE10',
        'discount_total' => '10.0000',
    ]);

    app(SyncPendingCheckoutSessionDiscountAction::class)->handle($cart);

    expect(CheckoutSession::query()->count())->toBe(0);
});

test('does not update a completed session', function () {
    $coupon = Coupon::factory()->valid()->fixed(10)->create();
    $cart = Cart::factory()->create([
        'coupon_code' => $coupon->code,
        'discount_total' => '10.0000',
    ]);
    $session = CheckoutSession::factory()->completed()->create([
        'cart_id' => $cart->id,
        'coupon_id' => null,
        'coupon_code' => null,
        'discount_total' => '0.0000',
        'total' => '50.0000',
    ]);

    app(SyncPendingCheckoutSessionDiscountAction::class)->handle($cart);

    $session->refresh();

    expect($session->coupon_id)->toBeNull()
        ->and($session->coupon_code)->toBeNull()
        ->and($session->discount_total)->toBe('0.0000')
        ->and($session->total)->toBe('50.0000');
});
