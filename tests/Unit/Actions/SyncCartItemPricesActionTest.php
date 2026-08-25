<?php

declare(strict_types=1);

use App\Actions\SyncCartItemPricesAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

covers(SyncCartItemPricesAction::class);

uses()->group('flash-sale', 'cart');

test('reverts to base price when flash sale expires', function () {
    $product = Product::factory()->available()->create(['price' => '100.0000']);

    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '80.0000',
        'total_price' => '80.0000',
    ]);

    $action = app(SyncCartItemPricesAction::class);
    $cart = $action->handle($cart);

    $item = $cart->items->first();
    expect($item->unit_price)->toBe('100.0000')
        ->and($item->total_price)->toBe('100.0000');
});

test('does not update when price is unchanged', function () {
    $product = Product::factory()->available()->create(['price' => '50.0000']);

    $cart = Cart::factory()->create();
    $cartItem = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
    ]);

    $updatedAt = $cartItem->updated_at;

    $action = app(SyncCartItemPricesAction::class);
    $action->handle($cart);

    expect($cartItem->refresh()->updated_at->toDateTimeString())->toBe($updatedAt->toDateTimeString());
});

test('returns cart unchanged for empty cart', function () {
    $cart = Cart::factory()->create();

    $action = app(SyncCartItemPricesAction::class);
    $result = $action->handle($cart);

    expect($result->id)->toBe($cart->id);
});

test('syncs the compare at price when the product goes on sale', function () {
    $product = Product::factory()->available()->create([
        'price' => '80.0000',
        'compare_at_price' => '120.0000',
    ]);

    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '80.0000',
        'compare_at_price' => null,
        'total_price' => '80.0000',
    ]);

    $cart = app(SyncCartItemPricesAction::class)->handle($cart);

    expect($cart->items->first()->compare_at_price)->toBe('120.0000');
});

test('clears a stale compare at price when the sale ends', function () {
    $product = Product::factory()->available()->create([
        'price' => '120.0000',
        'compare_at_price' => null,
    ]);

    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '80.0000',
        'compare_at_price' => '120.0000',
        'total_price' => '80.0000',
    ]);

    $cart = app(SyncCartItemPricesAction::class)->handle($cart);

    $item = $cart->items->first();
    expect($item->unit_price)->toBe('120.0000')
        ->and($item->compare_at_price)->toBeNull();
});
