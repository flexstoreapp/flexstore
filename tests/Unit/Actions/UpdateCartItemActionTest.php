<?php

declare(strict_types=1);

use App\Actions\UpdateCartItemAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseMissing;

covers(UpdateCartItemAction::class);

uses()->group('actions', 'cart');

test('updates item quantity', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['track_stock' => false]);

    $item = CartItem::factory()
        ->for($cart)
        ->for($product)
        ->create([
            'quantity' => 2,
            'unit_price' => '25.0000',
            'total_price' => '50.0000',
        ]);

    $action = app(UpdateCartItemAction::class);
    $result = $action->handle($cart->id, $item->id, 5);

    $item->refresh();
    expect($item->quantity)->toBe(5)
        ->and($item->total_price)->toBe('125.0000')
        ->and($result->subtotal)->toBe('125.0000');
});

test('throws exception when item does not belong to cart', function () {
    $cart1 = Cart::factory()->create();
    $cart2 = Cart::factory()->create();
    $item = CartItem::factory()->for($cart2)->create();

    $action = app(UpdateCartItemAction::class);

    expect(fn () => $action->handle($cart1->id, $item->id, 3))
        ->toThrow(ValidationException::class);
});

test('deletes item when quantity is zero', function () {
    $cart = Cart::factory()->create();
    $item = CartItem::factory()->for($cart)->create([
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(UpdateCartItemAction::class);
    $result = $action->handle($cart->id, $item->id, 0);

    expect($result->items)->toHaveCount(0);
    assertDatabaseMissing('cart_items', ['id' => $item->id]);
});

test('deletes item when quantity is negative', function () {
    $cart = Cart::factory()->create();
    $item = CartItem::factory()->for($cart)->create([
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(UpdateCartItemAction::class);
    $result = $action->handle($cart->id, $item->id, -1);

    expect($result->items)->toHaveCount(0);
    assertDatabaseMissing('cart_items', ['id' => $item->id]);
});

test('throws exception when stock is insufficient', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 5,
    ]);

    $item = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(UpdateCartItemAction::class);

    expect(fn () => $action->handle($cart->id, $item->id, 10))
        ->toThrow(ValidationException::class);
});

test('allows update when stock tracking is disabled', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => false,
        'stock' => 0,
    ]);

    $item = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(UpdateCartItemAction::class);
    $result = $action->handle($cart->id, $item->id, 100);

    expect($result->items()->first()->quantity)->toBe(100);
    expect($item->refresh()->quantity)->toBe(100);
});

test('checks variant stock when variant exists', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => false,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'track_stock' => true,
        'stock' => 3,
    ]);

    $item = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    $action = app(UpdateCartItemAction::class);

    expect(fn () => $action->handle($cart->id, $item->id, 5))
        ->toThrow(ValidationException::class);
});

test('allows update when variant stock is sufficient', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'track_stock' => false,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'track_stock' => true,
        'stock' => 10,
    ]);

    $item = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(UpdateCartItemAction::class);
    $result = $action->handle($cart->id, $item->id, 5);

    expect($item->refresh()->quantity)->toBe(5)
        ->and($result->subtotal)->toBe('125.0000');
});

test('refreshes cart totals after update', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'total' => '100.0000',
    ]);

    $product = Product::factory()->create([
        'track_stock' => false,
        'is_active' => true,
    ]);

    $item1 = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '30.0000',
        'total_price' => '60.0000',
    ]);

    $item2 = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '40.0000',
        'total_price' => '40.0000',
    ]);

    $action = app(UpdateCartItemAction::class);
    $result = $action->handle($cart->id, $item1->id, 5);

    expect($result->subtotal)->toBe('190.0000')
        ->and($result->total)->toBe('190.0000');
});

test('handles decimal prices correctly', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '19.9900',
        'track_stock' => false,
        'is_active' => true,
    ]);
    $item = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '19.9900',
        'total_price' => '39.9800',
    ]);

    $action = app(UpdateCartItemAction::class);
    $result = $action->handle($cart->id, $item->id, 3);

    $item->refresh();
    expect($item->quantity)->toBe(3)
        ->and($item->total_price)->toBe('59.9700')
        ->and($result->subtotal)->toBe('59.9700');
});
