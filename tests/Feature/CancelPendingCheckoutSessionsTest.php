<?php

declare(strict_types=1);

use App\Actions\StoreCheckoutSessionAction;
use App\DTOs\StoreCheckoutInput;
use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\StockReservation;

covers(StoreCheckoutSessionAction::class);

uses()->group('checkout');

function createCheckoutScenario(): array
{
    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => true,
        'stock' => 100,
    ]);

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '5.0000']);
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $checkoutData = [
        'customer_email' => 'customer@example.com',
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $paymentGateway->id,
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ];

    return compact('product', 'cart', 'shippingRate', 'paymentGateway', 'checkoutData');
}

test('re-checkout reuses previous pending session for same cart', function () {
    ['cart' => $cart, 'checkoutData' => $checkoutData] = createCheckoutScenario();

    $pendingSession = CheckoutSession::factory()->pending()->create(['cart_id' => $cart->id]);

    $action = app(StoreCheckoutSessionAction::class);
    $result = $action->handle(StoreCheckoutInput::fromArray($checkoutData), $cart->id);
    $reusedSession = $result->checkoutSession;

    expect($reusedSession->id)->toBe($pendingSession->id)
        ->and($reusedSession->status)->toBe(CheckoutSessionStatus::Pending)
        ->and($reusedSession->step)->toBe(CheckoutStep::PaymentInitiated)
        ->and($reusedSession->cart_id)->toBe($cart->id);
});

test('stock reservations are released and recreated on re-checkout', function () {
    ['product' => $product, 'cart' => $cart, 'checkoutData' => $checkoutData] = createCheckoutScenario();

    $pendingSession = CheckoutSession::factory()->pending()->create(['cart_id' => $cart->id]);
    StockReservation::factory()->create([
        'checkout_session_id' => $pendingSession->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    expect(StockReservation::where('checkout_session_id', $pendingSession->id)->count())->toBe(1);

    $action = app(StoreCheckoutSessionAction::class);
    $result = $action->handle(StoreCheckoutInput::fromArray($checkoutData), $cart->id);
    $reusedSession = $result->checkoutSession;

    expect($reusedSession->id)->toBe($pendingSession->id)
        ->and(StockReservation::where('checkout_session_id', $reusedSession->id)->count())->toBe(1);
});

test('re-checkout reuses initiated session without releasing stock', function () {
    ['cart' => $cart, 'checkoutData' => $checkoutData] = createCheckoutScenario();

    $initiatedSession = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'customer_email' => 'guest@example.com',
    ]);

    $action = app(StoreCheckoutSessionAction::class);
    $result = $action->handle(StoreCheckoutInput::fromArray($checkoutData), $cart->id);
    $reusedSession = $result->checkoutSession;

    expect($reusedSession->id)->toBe($initiatedSession->id)
        ->and($reusedSession->step)->toBe(CheckoutStep::PaymentInitiated)
        ->and(StockReservation::where('checkout_session_id', $reusedSession->id)->count())->toBe(1);
});

test('re-checkout cancels stale sessions and keeps the latest', function () {
    ['cart' => $cart, 'checkoutData' => $checkoutData] = createCheckoutScenario();

    $staleSession1 = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'created_at' => now()->subMinutes(10),
    ]);
    $staleSession2 = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'created_at' => now()->subMinutes(5),
    ]);
    $latestSession = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'created_at' => now(),
    ]);

    $action = app(StoreCheckoutSessionAction::class);
    $result = $action->handle(StoreCheckoutInput::fromArray($checkoutData), $cart->id);

    expect($result->checkoutSession->id)->toBe($latestSession->id)
        ->and($staleSession1->refresh()->status)->toBe(CheckoutSessionStatus::Canceled)
        ->and($staleSession2->refresh()->status)->toBe(CheckoutSessionStatus::Canceled)
        ->and($latestSession->refresh()->status)->toBe(CheckoutSessionStatus::Pending);
});

test('does not cancel sessions from different carts', function () {
    ['checkoutData' => $checkoutData] = createCheckoutScenario();

    $otherCart = Cart::factory()->create();
    $otherSession = CheckoutSession::factory()->pending()->create(['cart_id' => $otherCart->id]);

    $product = Product::factory()->available()->create(['price' => '50.0000', 'track_stock' => true, 'stock' => 100]);
    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $action = app(StoreCheckoutSessionAction::class);
    $action->handle(StoreCheckoutInput::fromArray($checkoutData), $cart->id);

    expect($otherSession->refresh()->status)->toBe(CheckoutSessionStatus::Pending);
});

test('does not cancel completed sessions from same cart', function () {
    ['cart' => $cart, 'checkoutData' => $checkoutData] = createCheckoutScenario();

    $completedSession = CheckoutSession::factory()->completed()->create(['cart_id' => $cart->id]);

    $action = app(StoreCheckoutSessionAction::class);
    $action->handle(StoreCheckoutInput::fromArray($checkoutData), $cart->id);

    expect($completedSession->refresh()->status)->toBe(CheckoutSessionStatus::Completed);
});
