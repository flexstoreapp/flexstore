<?php

declare(strict_types=1);

use App\Actions\ApplyPendingCouponAction;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\withSession;

covers([
    CheckoutController::class,
    ApplyPendingCouponAction::class,
]);

uses()->group('checkout');

function pendingCouponCart(string $subtotal = '100.0000'): Cart
{
    $cart = Cart::factory()->create(['subtotal' => $subtotal]);

    $product = Product::factory()->create(['price' => $subtotal]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $subtotal,
        'total_price' => $subtotal,
    ]);

    return $cart;
}

test('auto-applies pending coupon at checkout and clears the session', function () {
    $cart = pendingCouponCart();
    $coupon = Coupon::factory()->percentage(10)->valid()->create();

    withSession(['pending_coupon' => $coupon->code])
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('checkout.create'))
        ->assertOk()
        ->assertSessionMissing('pending_coupon');

    assertDatabaseHas('carts', [
        'id' => $cart->id,
        'coupon_code' => $coupon->code,
        'discount_total' => '10.0000',
    ]);
});

test('does not apply an invalid pending coupon and surfaces no error', function () {
    $cart = pendingCouponCart();

    withSession(['pending_coupon' => 'NOPE'])
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('checkout.create'))
        ->assertOk()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('carts', [
        'id' => $cart->id,
        'coupon_code' => null,
    ]);
});

test('does not override a coupon already applied to the cart', function () {
    $cart = pendingCouponCart();
    $applied = Coupon::factory()->percentage(25)->valid()->create();
    $cart->update(['coupon_code' => $applied->code, 'discount_total' => '25.0000']);

    $pending = Coupon::factory()->percentage(10)->valid()->create();

    withSession(['pending_coupon' => $pending->code])
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('checkout.create'))
        ->assertOk()
        ->assertSessionHas('pending_coupon', $pending->code);

    assertDatabaseHas('carts', [
        'id' => $cart->id,
        'coupon_code' => $applied->code,
    ]);
});

test('keeps the pending coupon when the cart is empty', function () {
    $cart = Cart::factory()->create(['subtotal' => '0.0000']);

    withSession(['pending_coupon' => 'SAVE10'])
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('checkout.create'))
        ->assertOk()
        ->assertSessionHas('pending_coupon', 'SAVE10');
});
