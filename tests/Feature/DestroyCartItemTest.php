<?php

declare(strict_types=1);

use App\Actions\DestroyCartItemAction;
use App\Http\Controllers\Storefront\CartItemController;
use App\Models\Cart;
use App\Models\CartItem;

use function Pest\Laravel\delete;
use function Pest\Laravel\withUnencryptedCookie;

covers(CartItemController::class, DestroyCartItemAction::class);

uses()->group('cart');

test('deletes cart item', function () {
    $cart = Cart::factory()->create();
    $item = CartItem::factory()->for($cart)->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->delete(route('cart.items.destroy', $item));

    $response->assertRedirectBack();

    expect(CartItem::query()->find($item->id))->toBeNull();
});

test('validates cart item exists when deleting', function () {
    $response = delete(route('cart.items.destroy', 9999));

    $response->assertNotFound();
});
