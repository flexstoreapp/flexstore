<?php

declare(strict_types=1);

use App\Enums\CheckoutSessionStatus;
use App\Http\Controllers\Api\V1\CheckoutDraftController;
use App\Http\Controllers\Api\V1\CheckoutOptionController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(CheckoutDraftController::class, CheckoutOptionController::class);

uses()->group('api');

function apiDraftCart(): Cart
{
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->available()->create(['price' => '50.0000']);

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
    ]);

    return $cart;
}

test('a draft creates the pending session that abandoned checkout recovery needs', function (): void {
    $cart = apiDraftCart();

    patchJson(route('api.v1.checkout.draft.update'), [
        'customer_email' => 'buyer@example.com',
        'shipping_address' => ['first_name' => 'John', 'country_code' => 'US', 'city' => 'New York'],
    ], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('cart_token', $cart->id)
        ->assertJsonPath('coupon_removed', false);

    assertDatabaseHas('checkout_sessions', [
        'cart_id' => $cart->id,
        'customer_email' => 'buyer@example.com',
        'status' => CheckoutSessionStatus::Pending->value,
    ]);
});

test('a later draft updates the same session instead of creating another', function (): void {
    $cart = apiDraftCart();

    patchJson(route('api.v1.checkout.draft.update'), ['customer_email' => 'buyer@example.com'], ['X-Cart-Token' => $cart->id])
        ->assertOk();
    patchJson(route('api.v1.checkout.draft.update'), ['notes' => 'Leave at the door.'], ['X-Cart-Token' => $cart->id])
        ->assertOk();

    expect(CheckoutSession::query()->where('cart_id', $cart->id)->count())->toBe(1);
});

test('a draft rejects an invalid email', function (): void {
    patchJson(route('api.v1.checkout.draft.update'), ['customer_email' => 'not-an-email'])
        ->assertJsonValidationErrors('customer_email');
});

test('selecting shipping and payment options stores them on the cart', function (): void {
    $cart = apiDraftCart();
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $rate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '10.0000']);
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    postJson(route('api.v1.checkout.options.store'), [
        'shipping_rate_id' => $rate->id,
        'payment_gateway_id' => $gateway->id,
    ], ['X-Cart-Token' => $cart->id])->assertOk();

    assertDatabaseHas('carts', [
        'id' => $cart->id,
        'shipping_rate_id' => $rate->id,
        'payment_gateway_id' => $gateway->id,
    ]);
});

test('an inactive shipping rate is rejected', function (): void {
    $cart = apiDraftCart();
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $rate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->create(['is_active' => false]);

    postJson(route('api.v1.checkout.options.store'), ['shipping_rate_id' => $rate->id], ['X-Cart-Token' => $cart->id])
        ->assertJsonValidationErrors('shipping_rate_id');
});
