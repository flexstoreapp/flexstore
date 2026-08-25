<?php

declare(strict_types=1);

use App\Actions\UpdateCartItemAction;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

use function Pest\Laravel\patch;
use function Pest\Laravel\withUnencryptedCookie;

covers(CartItemController::class, UpdateCartItemRequest::class, UpdateCartItemAction::class);

uses()->group('cart');

test('updates cart item quantity', function () {
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

    $response = withUnencryptedCookie('cart_id', $cart->id)->patch(route('cart.items.update', $item), [
        'quantity' => 5,
    ]);

    $response->assertRedirectBack();

    expect($item->refresh()->quantity)->toBe(5);
});

test('removes item when quantity set to zero', function () {
    $cart = Cart::factory()->create();
    $item = CartItem::factory()->for($cart)->create([
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->patch(route('cart.items.update', $item), [
        'quantity' => 0,
    ]);

    $response->assertRedirectBack();

    expect(CartItem::query()->find($item->id))->toBeNull();
});

test('validates quantity when updating item', function () {
    $cart = Cart::factory()->create();
    $item = CartItem::factory()->for($cart)->create();

    $response = patch(route('cart.items.update', $item), []);

    $response->assertRedirectBack()
        ->assertInvalid('quantity');
});
