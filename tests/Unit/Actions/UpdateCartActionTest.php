<?php

declare(strict_types=1);

use App\Actions\UpdateCartAction;
use App\DTOs\UpdateCartInput;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;

covers(UpdateCartAction::class, UpdateCartInput::class);

uses()->group('actions', 'cart');

test('updates shipping rate and recalculates totals', function () {
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

    $action = app(UpdateCartAction::class);
    $result = $action->handle($cart, UpdateCartInput::fromArray(['shipping_rate_id' => $shippingRate->id]));

    expect($result->shipping_rate_id)->toBe($shippingRate->id)
        ->and($result->shipping_total)->toBe('15.0000')
        ->and($result->total)->toBe('115.0000');
});

test('updates payment gateway without recalculating totals', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'total' => '100.0000',
    ]);

    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $action = app(UpdateCartAction::class);
    $result = $action->handle($cart, UpdateCartInput::fromArray(['payment_gateway_id' => $gateway->id]));

    expect($result->payment_gateway_id)->toBe($gateway->id)
        ->and($result->total)->toBe('100.0000');
});

test('updates both shipping rate and payment gateway', function () {
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

    $action = app(UpdateCartAction::class);
    $result = $action->handle($cart, UpdateCartInput::fromArray([
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $gateway->id,
    ]));

    expect($result->shipping_rate_id)->toBe($shippingRate->id)
        ->and($result->shipping_total)->toBe('10.0000')
        ->and($result->payment_gateway_id)->toBe($gateway->id)
        ->and($result->total)->toBe('110.0000');
});

test('preserves existing values when data keys are absent', function () {
    $shippingRate = ShippingRate::factory()->active()->create(['rate' => '5.0000']);
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $cart = Cart::factory()->create([
        'shipping_rate_id' => $shippingRate->id,
        'shipping_total' => '5.0000',
        'payment_gateway_id' => $gateway->id,
        'subtotal' => '50.0000',
        'total' => '55.0000',
    ]);

    $action = app(UpdateCartAction::class);
    $result = $action->handle($cart, UpdateCartInput::fromArray([]));

    expect($result->shipping_rate_id)->toBe($shippingRate->id)
        ->and($result->shipping_total)->toBe('5.0000')
        ->and($result->payment_gateway_id)->toBe($gateway->id)
        ->and($result->total)->toBe('55.0000');
});

test('handles free shipping rate with null rate', function () {
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

    $freeRate = ShippingRate::factory()->active()->create([
        'type' => App\Enums\ShippingRateType::Free,
        'rate' => null,
    ]);

    $action = app(UpdateCartAction::class);
    $result = $action->handle($cart, UpdateCartInput::fromArray(['shipping_rate_id' => $freeRate->id]));

    expect($result->shipping_rate_id)->toBe($freeRate->id)
        ->and($result->shipping_total)->toBe('0.0000')
        ->and($result->total)->toBe('100.0000');
});

test('replaces existing shipping rate with new one', function () {
    $oldRate = ShippingRate::factory()->active()->create(['rate' => '5.0000']);
    $newRate = ShippingRate::factory()->active()->create(['rate' => '20.0000']);

    $cart = Cart::factory()->create([
        'shipping_rate_id' => $oldRate->id,
        'shipping_total' => '5.0000',
        'subtotal' => '100.0000',
        'total' => '105.0000',
    ]);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $action = app(UpdateCartAction::class);
    $result = $action->handle($cart, UpdateCartInput::fromArray(['shipping_rate_id' => $newRate->id]));

    expect($result->shipping_rate_id)->toBe($newRate->id)
        ->and($result->shipping_total)->toBe('20.0000')
        ->and($result->total)->toBe('120.0000');
});

test('clears the quote reference when switching to a non-live rate', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
        'total' => '110.0000',
        'shipping_total' => '10.0000',
    ]);
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $flatRate = ShippingRate::factory()->active()->flatRate()->create(['rate' => '7.0000']);

    $action = app(UpdateCartAction::class);
    $result = $action->handle($cart, UpdateCartInput::fromArray([
        'shipping_rate_id' => $flatRate->id,
    ]));

    expect($result->shipping_total)->toBe('7.0000')
        ->and($result->shipping_quote_reference)->toBeNull()
        ->and($result->total)->toBe('107.0000');
});
