<?php

declare(strict_types=1);

use App\Actions\ClearCartAction;
use App\Models\Cart;
use App\Models\CartItem;

use function Pest\Laravel\assertDatabaseMissing;

covers(ClearCartAction::class);

uses()->group('actions', 'cart');

test('removes all items from cart', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->count(3)->create();

    expect($cart->items)->toHaveCount(3);

    $action = new ClearCartAction();
    $result = $action->handle($cart->fresh());

    expect($result->items)->toHaveCount(0)
        ->and(CartItem::query()->where('cart_id', $cart->id)->count())->toBe(0);
});

test('resets cart totals to zero', function () {
    $cart = Cart::factory()->create([
        'shipping_rate_id' => App\Models\ShippingRate::factory(),
        'payment_gateway_id' => App\Models\PaymentGateway::factory()->cod()->active(),
        'subtotal' => '100.0000',
        'discount_total' => '10.0000',
        'shipping_total' => '5.0000',
        'tax_total' => '8.0000',
        'total' => '103.0000',
        'coupon_code' => 'SAVE10',
    ]);

    CartItem::factory()->for($cart)->create();

    $action = new ClearCartAction();
    $result = $action->handle($cart);

    expect($result->shipping_rate_id)->toBeNull()
        ->and($result->payment_gateway_id)->toBeNull()
        ->and($result->subtotal)->toBe('0.0000')
        ->and($result->discount_total)->toBe('0.0000')
        ->and($result->shipping_total)->toBe('0.0000')
        ->and($result->tax_total)->toBe('0.0000')
        ->and($result->total)->toBe('0.0000')
        ->and($result->coupon_code)->toBeNull();
});

test('clears coupon code', function () {
    $cart = Cart::factory()->create([
        'coupon_code' => 'DISCOUNT20',
    ]);

    CartItem::factory()->for($cart)->create();

    $action = new ClearCartAction();
    $result = $action->handle($cart);

    expect($result->coupon_code)->toBeNull();
});

test('handles empty cart', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '50.0000',
        'total' => '50.0000',
    ]);

    $action = new ClearCartAction();
    $result = $action->handle($cart);

    expect($result->items)->toHaveCount(0)
        ->and($result->subtotal)->toBe('0.0000')
        ->and($result->total)->toBe('0.0000');
});

test('deletes cart items from database', function () {
    $cart = Cart::factory()->create();
    $item1 = CartItem::factory()->for($cart)->create();
    $item2 = CartItem::factory()->for($cart)->create();

    $action = new ClearCartAction();
    $action->handle($cart);

    assertDatabaseMissing('cart_items', ['id' => $item1->id]);
    assertDatabaseMissing('cart_items', ['id' => $item2->id]);
});

test('refreshes cart totals after clearing', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '5.0000',
        'shipping_total' => '10.0000',
        'tax_total' => '8.5000',
        'total' => '113.5000',
    ]);

    CartItem::factory()->for($cart)->create([
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
    ]);

    $action = new ClearCartAction();
    $result = $action->handle($cart);

    expect($result->subtotal)->toBe('0.0000')
        ->and($result->total)->toBe('0.0000');
});
