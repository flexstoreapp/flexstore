<?php

declare(strict_types=1);

use App\Actions\ResolveCartAction;
use App\Models\Cart;
use App\Models\User;

covers(ResolveCartAction::class);

uses()->group('queries', 'cart');

test('creates a new cart for guest customer', function () {
    $query = app(ResolveCartAction::class);

    $cart = $query->handle();

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->id)->not()->toBeNull()
        ->and($cart->subtotal)->toBe('0.0000')
        ->and($cart->discount_total)->toBe('0.0000')
        ->and($cart->shipping_total)->toBe('0.0000')
        ->and($cart->tax_total)->toBe('0.0000')
        ->and($cart->total)->toBe('0.0000');
});

test('reuses existing customer cart when present', function () {
    $customer = User::factory()->customer()->create();
    $existing = Cart::factory()->create([
        'customer_id' => $customer->id,
    ]);

    $query = app(ResolveCartAction::class);

    $cart = $query->handle(customer: $customer);

    expect($cart->id)->toBe($existing->id)
        ->and($cart->customer_id)->toBe($customer->id);
});

test('assigns guest cart to customer when customer logs in', function () {
    $customer = User::factory()->customer()->create();
    $guestCart = Cart::factory()->create([
        'customer_id' => null,
    ]);

    $query = app(ResolveCartAction::class);

    $cart = $query->handle($guestCart->id, $customer);

    expect($cart->id)->toBe($guestCart->id)
        ->and($cart->customer_id)->toBe($customer->id);
});

test('returns cached cart when cart id matches', function () {
    $cart = Cart::factory()->create();
    $query = app(ResolveCartAction::class);

    $result1 = $query->handle($cart->id);
    $result2 = $query->handle($cart->id);

    expect($result1->id)->toBe($cart->id)
        ->and($result2->id)->toBe($cart->id);
});

test('does not return cached cart when different cart id requested', function () {
    $cart1 = Cart::factory()->create();
    $cart2 = Cart::factory()->create();
    $query = app(ResolveCartAction::class);

    $result1 = $query->handle($cart1->id);
    $result2 = $query->handle($cart2->id);

    expect($result1->id)->toBe($cart1->id)
        ->and($result2->id)->toBe($cart2->id);
});
