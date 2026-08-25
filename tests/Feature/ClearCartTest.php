<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\CartController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\withUnencryptedCookie;

covers(CartController::class);

uses()->group('cart');

test('clears all items from the cart', function () {
    $cart = Cart::factory()->create(['coupon_code' => 'SAVE10', 'discount_total' => '10.0000']);
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create(['product_id' => $product->id]);

    withUnencryptedCookie('cart_id', $cart->id)->delete(route('cart.destroy'))->assertRedirect();

    assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    expect($cart->refresh())
        ->coupon_code->toBeNull()
        ->total->toBe('0.0000');
});

test('clears the authenticated user cart', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->create(['customer_id' => $user->id]);
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create(['product_id' => $product->id]);

    actingAs($user)->delete(route('cart.destroy'))->assertRedirect();

    assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
});
