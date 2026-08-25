<?php

declare(strict_types=1);

use App\Actions\InitiateCheckoutSessionAction;
use App\Actions\SyncMediaAction;
use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Media;
use App\Models\Product;
use App\Models\User;

covers(InitiateCheckoutSessionAction::class);

uses()->group('checkout');

test('creates initiated session for cart', function () {
    $cart = Cart::factory()->create();

    $action = app(InitiateCheckoutSessionAction::class);
    $session = $action->handle($cart->id, 'customer@example.com');

    expect($session->cart_id)->toBe($cart->id)
        ->and($session->customer_email)->toBe('customer@example.com')
        ->and($session->status)->toBe(CheckoutSessionStatus::Pending)
        ->and($session->step)->toBe(CheckoutStep::ContactInformation)
        ->and($session->expires_at)->toBeNull()
        ->and($session->customer_id)->toBeNull();
});

test('updates existing pending session email', function () {
    $cart = Cart::factory()->create();
    $existing = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'customer_email' => 'old@example.com',
    ]);

    $action = app(InitiateCheckoutSessionAction::class);
    $session = $action->handle($cart->id, 'new@example.com');

    expect($session->id)->toBe($existing->id)
        ->and($session->customer_email)->toBe('new@example.com');
});

test('does not duplicate session for same cart', function () {
    $cart = Cart::factory()->create();
    CheckoutSession::factory()->pending()->create(['cart_id' => $cart->id]);

    $action = app(InitiateCheckoutSessionAction::class);
    $action->handle($cart->id, 'customer@example.com');

    expect(CheckoutSession::where('cart_id', $cart->id)->count())->toBe(1);
});

test('creates new session when only completed sessions exist', function () {
    $cart = Cart::factory()->create();
    $completed = CheckoutSession::factory()->completed()->create(['cart_id' => $cart->id]);

    $action = app(InitiateCheckoutSessionAction::class);
    $session = $action->handle($cart->id, 'customer@example.com');

    expect($session->id)->not->toBe($completed->id)
        ->and($session->step)->toBe(CheckoutStep::ContactInformation)
        ->and(CheckoutSession::where('cart_id', $cart->id)->count())->toBe(2);
});

test('sets customer id when provided', function () {
    $cart = Cart::factory()->create();
    $customer = User::factory()->customer()->create();

    $action = app(InitiateCheckoutSessionAction::class);
    $session = $action->handle($cart->id, 'customer@example.com', $customer->id);

    expect($session->customer_id)->toBe($customer->id);
});

test('snapshots cart items into the session', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['title' => 'Coffee Mug']);
    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '10.0000',
        'total_price' => '20.0000',
    ]);

    $action = app(InitiateCheckoutSessionAction::class);
    $session = $action->handle($cart->id, 'customer@example.com');

    expect($session->items)->toHaveCount(1)
        ->and($session->items[0]['product_id'])->toBe($product->id)
        ->and($session->items[0]['quantity'])->toBe(2)
        ->and($session->items[0]['total_price'])->toBe('20.0000')
        ->and($session->subtotal)->toBe('20.0000')
        ->and($session->total)->toBe('20.0000');
});

test('refreshes items snapshot when re-initiated', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'quantity' => 1,
        'unit_price' => '5.0000',
        'total_price' => '5.0000',
    ]);

    $action = app(InitiateCheckoutSessionAction::class);
    $action->handle($cart->id, 'customer@example.com');

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'quantity' => 3,
        'unit_price' => '4.0000',
        'total_price' => '12.0000',
    ]);

    $session = $action->handle($cart->id, 'customer@example.com');

    expect($session->items)->toHaveCount(2)
        ->and($session->subtotal)->toBe('17.0000');
});

test('snapshots currency code when provided', function () {
    $cart = Cart::factory()->create();

    $action = app(InitiateCheckoutSessionAction::class);
    $session = $action->handle($cart->id, 'customer@example.com', null, 'EUR');

    expect($session->currency_code)->toBe('EUR');
});

test('snapshots the small thumbnail from product media', function () {
    $cart = Cart::factory()->create();
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);
    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '10.0000',
        'total_price' => '10.0000',
    ]);

    $session = app(InitiateCheckoutSessionAction::class)->handle($cart->id, 'customer@example.com');

    expect($session->items[0])->toHaveKey('thumbnail_url')
        ->and($session->items[0]['thumbnail_url'])->toBe($media->small_thumbnail_url);
});

test('handles empty cart gracefully', function () {
    $cart = Cart::factory()->create();

    $session = app(InitiateCheckoutSessionAction::class)->handle($cart->id, 'customer@example.com');

    expect($session->items)->toBe([])
        ->and($session->subtotal)->toBe('0.0000')
        ->and($session->total)->toBe('0.0000');
});
