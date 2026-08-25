<?php

declare(strict_types=1);

use App\Actions\ResolveVisitorCartAction;
use App\Models\Cart;
use App\Models\User;

covers(ResolveVisitorCartAction::class);

uses()->group('actions', 'cart');

test('resolves the cart for the given id', function () {
    $cart = Cart::factory()->create();

    $resolved = app(ResolveVisitorCartAction::class)->handle($cart->id, null);

    expect($resolved->id)->toBe($cart->id);
});

test('creates a cart when the visitor has none', function () {
    $resolved = app(ResolveVisitorCartAction::class)->handle(null, null);

    expect($resolved)->toBeInstanceOf(Cart::class)
        ->and(Cart::query()->whereKey($resolved->id)->exists())->toBeTrue();
});

test('memoizes the cart for the lifetime of the request', function () {
    $action = app(ResolveVisitorCartAction::class);
    $cart = Cart::factory()->create();
    $other = Cart::factory()->create();

    $first = $action->handle($cart->id, null);
    $second = $action->handle($other->id, null);

    expect($second)->toBe($first)
        ->and($second->id)->toBe($cart->id);
});

test('resolves the cart of an authenticated customer', function () {
    $customer = User::factory()->create();
    $cart = Cart::factory()->create(['customer_id' => $customer->id]);

    $resolved = app(ResolveVisitorCartAction::class)->handle(null, $customer);

    expect($resolved->id)->toBe($cart->id);
});
