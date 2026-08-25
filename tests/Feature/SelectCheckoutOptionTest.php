<?php

declare(strict_types=1);

use App\Actions\ApplyCheckoutOptionsAction;
use App\Actions\ResolveCartAction;
use App\Actions\UpdateCartAction;
use App\Actions\UpdatePendingCheckoutSessionAction;
use App\Http\Controllers\Storefront\CheckoutOptionController;
use App\Http\Requests\Storefront\StoreCheckoutOptionRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;

use function Pest\Laravel\withUnencryptedCookie;

covers([
    CheckoutOptionController::class,
    StoreCheckoutOptionRequest::class,
    ApplyCheckoutOptionsAction::class,
    UpdateCartAction::class,
    UpdatePendingCheckoutSessionAction::class,
    ResolveCartAction::class,
]);

uses()->group('checkout');

test('selects shipping rate on cart and recalculates totals', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'total' => '100.0000',
    ]);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $shippingRate = ShippingRate::factory()->active()->create(['rate' => '15.0000']);

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'shipping_rate_id' => $shippingRate->id,
    ])->assertRedirect();

    $cart->refresh();

    expect($cart->shipping_rate_id)->toBe($shippingRate->id)
        ->and($cart->shipping_total)->toBe('15.0000')
        ->and($cart->total)->toBe('115.0000');
});

test('selects payment gateway on cart', function () {
    $cart = Cart::factory()->create();
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'payment_gateway_id' => $gateway->id,
    ])->assertRedirect();

    $cart->refresh();

    expect($cart->payment_gateway_id)->toBe($gateway->id);
});

test('selects both shipping rate and payment gateway in single request', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'total' => '100.0000',
    ]);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $shippingRate = ShippingRate::factory()->active()->create(['rate' => '10.0000']);
    $gateway = PaymentGateway::factory()->stripe()->active()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $gateway->id,
    ])->assertRedirect();

    $cart->refresh();

    expect($cart->shipping_rate_id)->toBe($shippingRate->id)
        ->and($cart->shipping_total)->toBe('10.0000')
        ->and($cart->payment_gateway_id)->toBe($gateway->id);
});

test('snapshots shipping and payment selection into pending session', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'subtotal' => '50.0000',
        'total' => '50.0000',
    ]);
    $shippingRate = ShippingRate::factory()->active()->create(['rate' => '7.0000']);
    $gateway = PaymentGateway::query()->first() ?? PaymentGateway::factory()->stripe()->active()->create();
    $gateway->update(['is_active' => true]);

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $gateway->id,
    ])->assertRedirect();

    $session->refresh();
    expect($session->shipping_rate_id)->toBe($shippingRate->id)
        ->and($session->payment_gateway_id)->toBe($gateway->id)
        ->and($session->shipping_total)->toBe('7.0000')
        ->and($session->total)->toBe('57.0000')
        ->and($session->shipping_carrier_name)->not->toBeNull()
        ->and($session->payment_gateway_name)->not->toBeNull();
});

test('clears shipping selection on cart and pending session when shipping_rate_id is null', function () {
    $shippingRate = ShippingRate::factory()->active()->create(['rate' => '8.0000']);
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'shipping_rate_id' => $shippingRate->id,
        'shipping_total' => '8.0000',
        'total' => '108.0000',
    ]);
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);
    $session = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'subtotal' => '100.0000',
        'shipping_rate_id' => $shippingRate->id,
        'shipping_total' => '8.0000',
        'total' => '108.0000',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'shipping_rate_id' => null,
    ])->assertRedirect();

    $cart->refresh();
    $session->refresh();

    expect($cart->shipping_rate_id)->toBeNull()
        ->and($cart->shipping_total)->toBe('0.0000')
        ->and($cart->total)->toBe('100.0000')
        ->and($session->shipping_rate_id)->toBeNull()
        ->and($session->shipping_total)->toBe('0.0000')
        ->and($session->total)->toBe('100.0000');
});

test('validates shipping rate exists and is active', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'shipping_rate_id' => 99999,
    ])->assertInvalid('shipping_rate_id');

    $inactiveRate = ShippingRate::factory()->inactive()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'shipping_rate_id' => $inactiveRate->id,
    ])->assertInvalid('shipping_rate_id');
});

test('validates payment gateway exists and is active', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'payment_gateway_id' => 99999,
    ])->assertInvalid('payment_gateway_id');

    $inactiveGateway = PaymentGateway::factory()->cod()->inactive()->create();

    withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.options.store'), [
        'payment_gateway_id' => $inactiveGateway->id,
    ])->assertInvalid('payment_gateway_id');
});
