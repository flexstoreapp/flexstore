<?php

declare(strict_types=1);

use App\Actions\DestroyCartItemAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseMissing;

covers(DestroyCartItemAction::class);

uses()->group('actions', 'cart');

test('removes item from cart', function () {
    $cart = Cart::factory()->create();
    $item = CartItem::factory()->for($cart)->create([
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(DestroyCartItemAction::class);
    $result = $action->handle($cart->id, $item->id);

    expect($result->items)->toHaveCount(0)
        ->and($result->subtotal)->toBe('0.0000');

    assertDatabaseMissing('cart_items', ['id' => $item->id]);
});

test('throws exception when item does not belong to cart', function () {
    $cart1 = Cart::factory()->create();
    $cart2 = Cart::factory()->create();
    $item = CartItem::factory()->for($cart2)->create();

    $action = app(DestroyCartItemAction::class);

    expect(fn () => $action->handle($cart1->id, $item->id))
        ->toThrow(ValidationException::class);
});

test('refreshes cart totals after removing item', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'total' => '100.0000',
    ]);

    $item1 = CartItem::factory()->for($cart)->create([
        'quantity' => 2,
        'unit_price' => '30.0000',
        'total_price' => '60.0000',
    ]);

    $item2 = CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '40.0000',
        'total_price' => '40.0000',
    ]);

    $action = app(DestroyCartItemAction::class);
    $result = $action->handle($cart->id, $item1->id);

    expect($result->subtotal)->toBe('40.0000')
        ->and($result->total)->toBe('40.0000')
        ->and($result->items)->toHaveCount(1);
});

test('handles removing last item from cart', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '50.0000',
        'total' => '50.0000',
    ]);

    $item = CartItem::factory()->for($cart)->create([
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(DestroyCartItemAction::class);
    $result = $action->handle($cart->id, $item->id);

    expect($result->items)->toHaveCount(0)
        ->and($result->subtotal)->toBe('0.0000')
        ->and($result->total)->toBe('0.0000');
});

test('preserves other cart items when removing one', function () {
    $cart = Cart::factory()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $item1 = CartItem::factory()->for($cart)->create([
        'product_id' => $product1->id,
        'quantity' => 2,
        'unit_price' => '20.0000',
        'total_price' => '40.0000',
    ]);

    $item2 = CartItem::factory()->for($cart)->create([
        'product_id' => $product2->id,
        'quantity' => 1,
        'unit_price' => '30.0000',
        'total_price' => '30.0000',
    ]);

    $action = app(DestroyCartItemAction::class);
    $result = $action->handle($cart->id, $item1->id);

    expect($result->items)->toHaveCount(1)
        ->and($result->items->first()->product_id)->toBe($product2->id)
        ->and($result->subtotal)->toBe('30.0000');
});
