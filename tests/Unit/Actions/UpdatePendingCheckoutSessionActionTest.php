<?php

declare(strict_types=1);

use App\Actions\UpdatePendingCheckoutSessionAction;
use App\DTOs\CheckoutOptionsInput;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;

covers(UpdatePendingCheckoutSessionAction::class);

uses()->group('actions', 'checkout');

test('returns null when no pending session exists and no email provided', function () {
    $cart = Cart::factory()->create();

    $result = app(UpdatePendingCheckoutSessionAction::class)->handle($cart->id, CheckoutOptionsInput::fromArray(['notes' => 'hi']));

    expect($result)->toBeNull();
});

test('creates a session on the fly when email is provided', function () {
    $cart = Cart::factory()->create();

    $result = app(UpdatePendingCheckoutSessionAction::class)->handle(
        $cart->id,
        CheckoutOptionsInput::fromArray(['notes' => 'hi']),
        customerEmail: 'guest@example.com',
    );

    expect($result)->not->toBeNull()
        ->and($result->customer_email)->toBe('guest@example.com')
        ->and($result->notes)->toBe('hi');

    expect(CheckoutSession::where('cart_id', $cart->id)->count())->toBe(1);
});

test('snapshots shipping selection with names and totals', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'subtotal' => '100.0000',
        'total' => '100.0000',
    ]);
    $carrier = ShippingCarrier::factory()->create(['name' => ['en' => 'FedEx']]);
    $rate = ShippingRate::factory()->active()->create([
        'name' => ['en' => 'Express'],
        'rate' => '12.5000',
        'shipping_carrier_id' => $carrier->id,
    ]);

    app(UpdatePendingCheckoutSessionAction::class)->handle($cart->id, CheckoutOptionsInput::fromArray([
        'shipping_rate_id' => $rate->id,
    ]));

    $session->refresh();
    expect($session->shipping_rate_id)->toBe($rate->id)
        ->and($session->shipping_carrier_id)->toBe($carrier->id)
        ->and($session->shipping_total)->toBe('12.5000')
        ->and($session->total)->toBe('112.5000')
        ->and($session->getTranslation('shipping_rate_name', 'en'))->toBe('Express')
        ->and($session->getTranslation('shipping_carrier_name', 'en'))->toBe('FedEx');
});

test('subtracts an applied discount when snapshotting shipping', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'subtotal' => '100.0000',
        'discount_total' => '10.0000',
        'total' => '90.0000',
    ]);
    $rate = ShippingRate::factory()->active()->create(['rate' => '12.5000']);

    app(UpdatePendingCheckoutSessionAction::class)->handle($cart->id, CheckoutOptionsInput::fromArray([
        'shipping_rate_id' => $rate->id,
    ]));

    expect($session->refresh()->total)->toBe('102.5000');
});

test('snapshots payment gateway selection with name', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);
    $gateway = PaymentGateway::query()->first() ?? PaymentGateway::factory()->stripe()->active()->create();
    $gateway->update(['name' => ['en' => 'Stripe']]);

    app(UpdatePendingCheckoutSessionAction::class)->handle($cart->id, CheckoutOptionsInput::fromArray([
        'payment_gateway_id' => $gateway->id,
    ]));

    $session->refresh();
    expect($session->payment_gateway_id)->toBe($gateway->id)
        ->and($session->getTranslation('payment_gateway_name', 'en'))->toBe('Stripe');
});

test('snapshots address and notes', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);

    $address = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address_line_1' => '1 High St',
        'city' => 'London',
        'country_code' => 'GB',
    ];

    app(UpdatePendingCheckoutSessionAction::class)->handle($cart->id, CheckoutOptionsInput::fromArray([
        'shipping_address' => $address,
        'notes' => 'Leave at door',
    ]));

    $session->refresh();
    expect($session->shipping_address)->toMatchArray($address)
        ->and($session->notes)->toBe('Leave at door');
});

test('does not touch unrelated fields', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'notes' => 'original',
    ]);

    app(UpdatePendingCheckoutSessionAction::class)->handle($cart->id, CheckoutOptionsInput::fromArray([
        'payment_gateway_id' => null,
    ]));

    $session->refresh();
    expect($session->notes)->toBe('original');
});
