<?php

declare(strict_types=1);

use App\Enums\CheckoutSessionStatus;
use App\Enums\CouponType;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CheckoutCouponController;
use App\Http\Controllers\Api\V1\CheckoutPaymentOptionController;
use App\Http\Controllers\Api\V1\CheckoutSessionController;
use App\Http\Controllers\Api\V1\CheckoutShippingOptionController;
use App\Http\Requests\Api\V1\StoreCheckoutRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\StockReservation;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(
    CheckoutController::class,
    CheckoutSessionController::class,
    CheckoutShippingOptionController::class,
    CheckoutPaymentOptionController::class,
    CheckoutCouponController::class,
    StoreCheckoutRequest::class,
);

uses()->group('api');

function apiCheckoutCart(): Cart
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

function apiCheckoutPayload(ShippingRate $rate, PaymentGateway $gateway): array
{
    return [
        'customer_email' => 'customer@example.com',
        'shipping_rate_id' => $rate->id,
        'payment_gateway_id' => $gateway->id,
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ];
}

test('shipping options are listed for a destination', function (): void {
    $cart = apiCheckoutCart();
    $carrier = ShippingCarrier::factory()->active()->create(['name' => 'In-house']);
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $rate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()
        ->create(['rate' => '10.0000', 'name' => 'Standard']);

    postJson(route('api.v1.checkout.shipping-options'), [
        'shipping_address' => ['country_code' => 'US', 'state' => 'NY', 'postal_code' => '10001'],
    ], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonStructure(['data' => ['shipping', 'tax_estimate']])
        ->assertJsonPath('data.shipping.0.rate_id', $rate->id)
        ->assertJsonPath('data.shipping.0.name', 'Standard')
        ->assertJsonPath('data.shipping.0.carrier_name', 'In-house')
        ->assertJsonPath('data.shipping.0.rate', '10.0000');
});

test('payment options are listed for a destination', function (): void {
    $cart = apiCheckoutCart();
    $gateway = PaymentGateway::factory()->cod()->active()->create(['name' => 'Cash on delivery']);

    postJson(route('api.v1.checkout.payment-options'), [
        'address' => ['country_code' => 'US'],
    ], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('data.0.id', $gateway->id)
        ->assertJsonPath('data.0.name', 'Cash on delivery')
        ->assertJsonPath('data.0.driver', 'cod');
});

test('a coupon can be applied and removed', function (): void {
    $cart = apiCheckoutCart();
    Coupon::factory()->active()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
        'min_order_value' => null,
        'maximum_discount' => null,
        'usage_limit' => null,
        'usage_limit_per_customer' => null,
        'starts_at' => null,
        'expires_at' => null,
    ]);

    postJson(route('api.v1.checkout.coupons.store'), ['coupon_code' => 'SAVE10'], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('data.coupon_code', 'SAVE10');

    deleteJson(route('api.v1.checkout.coupons.destroy'), [], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('data.coupon_code', null);
});

test('a cash on delivery checkout creates a session and an order', function (): void {
    $cart = apiCheckoutCart();
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $rate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '10.0000']);
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $response = postJson(
        route('api.v1.checkout.store'),
        apiCheckoutPayload($rate, $gateway),
        ['X-Cart-Token' => $cart->id],
    );

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['checkout_session_id', 'payment_status', 'redirect_url', 'cancel_url']]);

    $session = CheckoutSession::query()->findOrFail($response->json('data.checkout_session_id'));
    $sessionId = $session->id;

    expect($session->cart_id)->toBe($cart->id)
        ->and($session->customer_email)->toBe('customer@example.com');

    getJson(route('api.v1.checkout.sessions.show', $sessionId), ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('data.status', CheckoutSessionStatus::Completed->value)
        ->assertJsonPath('data.checkout_session_id', $sessionId);
});

test('checkout fails validation without a shipping address', function (): void {
    $cart = apiCheckoutCart();
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    postJson(route('api.v1.checkout.store'), [
        'customer_email' => 'customer@example.com',
        'payment_gateway_id' => $gateway->id,
    ], ['X-Cart-Token' => $cart->id])
        ->assertJsonValidationErrors(['shipping_address', 'shipping_rate_id']);
});

test('a checkout session cannot be read by another visitor', function (): void {
    $cart = apiCheckoutCart();
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $rate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '10.0000']);
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $sessionId = postJson(
        route('api.v1.checkout.store'),
        apiCheckoutPayload($rate, $gateway),
        ['X-Cart-Token' => $cart->id],
    )->json('data.checkout_session_id');

    getJson(route('api.v1.checkout.sessions.show', $sessionId), [
        'X-Cart-Token' => Cart::factory()->create()->id,
    ])->assertForbidden();
});

test('abandoning checkout cancels the session and releases held stock', function (): void {
    $cart = apiCheckoutCart();
    $session = CheckoutSession::factory()->create([
        'cart_id' => $cart->id,
        'status' => CheckoutSessionStatus::Pending,
    ]);
    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $cart->items->first()->product_id,
    ]);

    deleteJson(route('api.v1.checkout.sessions.destroy', $session->id), [], ['X-Cart-Token' => $cart->id])
        ->assertOk();

    expect($session->refresh()->status)->toBe(CheckoutSessionStatus::Canceled)
        ->and(StockReservation::query()->where('checkout_session_id', $session->id)->exists())->toBeFalse();
});

test('another visitor cannot cancel a checkout session', function (): void {
    $cart = apiCheckoutCart();
    $session = CheckoutSession::factory()->create([
        'cart_id' => $cart->id,
        'status' => CheckoutSessionStatus::Pending,
    ]);

    deleteJson(route('api.v1.checkout.sessions.destroy', $session->id), [], [
        'X-Cart-Token' => Cart::factory()->create()->id,
    ])->assertForbidden();
});
