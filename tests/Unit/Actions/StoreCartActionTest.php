<?php

declare(strict_types=1);

use App\Actions\StoreCartAction;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Str;

covers(StoreCartAction::class);

uses()->group('actions', 'cart');

test('creates a new cart with zeroed totals', function () {
    $action = new StoreCartAction();

    $cart = $action->handle();

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->id)->not()->toBeNull()
        ->and($cart->customer_id)->toBeNull()
        ->and($cart->subtotal)->toBe('0.0000')
        ->and($cart->discount_total)->toBe('0.0000')
        ->and($cart->shipping_total)->toBe('0.0000')
        ->and($cart->tax_total)->toBe('0.0000')
        ->and($cart->total)->toBe('0.0000')
        ->and($cart->items)->toBeEmpty();
});

test('creates a cart with specified id', function () {
    $cartId = Str::uuid7()->toString();
    $action = new StoreCartAction();

    $cart = $action->handle($cartId);

    expect($cart->id)->toBe($cartId);
});

test('creates a cart for a customer', function () {
    $customer = User::factory()->customer()->create();
    $action = new StoreCartAction();

    $cart = $action->handle(customer: $customer);

    expect($cart->customer_id)->toBe($customer->id);
});
