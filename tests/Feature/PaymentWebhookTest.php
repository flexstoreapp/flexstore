<?php

declare(strict_types=1);

use App\Enums\CheckoutSessionStatus;
use App\Enums\PaymentSessionPurpose;
use App\Enums\PaymentSessionStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\PaymentWebhookController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Models\PaymentSession;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Payment\Drivers\MockDriver;
use App\Payment\PaymentManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

covers(PaymentWebhookController::class);

uses()->group('payment', 'webhook');

test('returns 400 for unknown driver', function () {
    $response = postJson(route('webhooks.payment', ['driver' => 'unknown']));

    $response->assertStatus(400);
});

test('returns 404 when gateway not found', function () {
    $response = postJson(route('webhooks.payment', ['driver' => 'stripe']));

    $response->assertStatus(404);
});

test('returns 403 when COD webhook signature verification fails', function () {
    PaymentGateway::factory()->cod()->create(['is_active' => true]);

    $response = postJson(route('webhooks.payment', ['driver' => 'cod']));

    $response->assertStatus(403);
});

test('returns 403 when Stripe webhook has invalid signature', function () {
    PaymentGateway::factory()->stripe()->create([
        'is_active' => true,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'signing_secret' => 'whsec_test_123',
        ],
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'stripe']),
        ['type' => 'checkout.session.completed'],
        ['Stripe-Signature' => 'invalid_signature']
    );

    $response->assertStatus(403);
});

test('returns 403 when Stripe webhook has no signature', function () {
    PaymentGateway::factory()->stripe()->create([
        'is_active' => true,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'signing_secret' => 'whsec_test_123',
        ],
    ]);

    $response = postJson(route('webhooks.payment', ['driver' => 'stripe']));

    $response->assertStatus(403);
});

test('returns 403 when Stripe webhook secret is missing', function () {
    PaymentGateway::factory()->stripe()->create([
        'is_active' => true,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
        ],
    ]);

    $response = postJson(route('webhooks.payment', ['driver' => 'stripe']));

    $response->assertStatus(403);
});

test('returns 403 when Razorpay webhook has invalid signature', function () {
    PaymentGateway::factory()->razorpay()->create([
        'is_active' => true,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'webhook_secret' => 'whsec_test_123',
        ],
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'razorpay']),
        ['event' => 'payment.captured'],
        ['X-Razorpay-Signature' => 'invalid_signature']
    );

    $response->assertStatus(403);
});

test('returns 403 when Razorpay webhook has no signature', function () {
    PaymentGateway::factory()->razorpay()->create([
        'is_active' => true,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'webhook_secret' => 'whsec_test_123',
        ],
    ]);

    $response = postJson(route('webhooks.payment', ['driver' => 'razorpay']));

    $response->assertStatus(403);
});

test('returns 403 when Razorpay webhook secret is missing', function () {
    PaymentGateway::factory()->razorpay()->create([
        'is_active' => true,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
        ],
    ]);

    $response = postJson(route('webhooks.payment', ['driver' => 'razorpay']));

    $response->assertStatus(403);
});

test('returns 403 when Paystack webhook has invalid signature', function () {
    PaymentGateway::factory()->paystack()->create(['is_active' => true]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'paystack']),
        ['event' => 'charge.success'],
        ['x-paystack-signature' => 'invalid_signature']
    );

    $response->assertStatus(403);
});

test('returns 403 when Paystack webhook has no signature', function () {
    PaymentGateway::factory()->paystack()->create(['is_active' => true]);

    $response = postJson(route('webhooks.payment', ['driver' => 'paystack']));

    $response->assertStatus(403);
});

test('paystack webhook with valid signature confirms checkout session and creates order', function () {
    $gateway = PaymentGateway::factory()->paystack()->create(['is_active' => true]);

    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => false,
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

    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'payment_gateway_id' => $gateway->id,
        'payment_gateway_name' => $gateway->getTranslations('name'),
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $carrier->id,
        'shipping_carrier_name' => $carrier->getTranslations('name'),
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
                'unit_price' => '50.0000',
                'total_price' => '50.0000',
                'product_title' => $product->getTranslations('title'),
                'product_sku' => $product->sku,
                'variant_title' => null,
                'variant_options' => [],
                'weight' => $product->weight,
                'weight_unit' => $product->weight_unit,
            ],
        ],
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '55.0000',
    ]);

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $session->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '55.0000',
        'status' => PaymentSessionStatus::Pending,
        'return_url' => 'https://example.com/success',
    ]);

    $body = json_encode([
        'event' => 'charge.success',
        'data' => [
            'id' => 6342936041,
            'reference' => $paymentSession->id,
            'status' => 'success',
            'channel' => 'card',
            'authorization' => ['brand' => 'visa', 'last4' => '4081'],
            'metadata' => ['payment_session_id' => $paymentSession->id],
        ],
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'paystack']),
        json_decode($body, true),
        ['x-paystack-signature' => hash_hmac('sha512', $body, $gateway->credentials['secret_key'])]
    );

    $response->assertNoContent();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed)
        ->and($session->order_id)->not->toBeNull();

    $order = Order::query()->find($session->order_id);
    expect($order)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);

    $transaction = OrderTransaction::query()->where('order_id', $order->id)->first();
    expect($transaction->gateway_reference)->toBe($paymentSession->id)
        ->and($transaction->payment_method)->toBe('card')
        ->and($transaction->payment_method_details)->toBe(['brand' => 'visa', 'last4' => '4081']);
});

test('returns 403 when Tap webhook has invalid signature', function () {
    PaymentGateway::factory()->tap()->create(['is_active' => true]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'tap']),
        ['id' => 'chg_1', 'status' => 'CAPTURED'],
        ['hashstring' => 'invalid_signature']
    );

    $response->assertStatus(403);
});

test('returns 403 when Tap webhook has no signature', function () {
    PaymentGateway::factory()->tap()->create(['is_active' => true]);

    $response = postJson(route('webhooks.payment', ['driver' => 'tap']));

    $response->assertStatus(403);
});

test('tap webhook with valid hashstring confirms checkout session and creates order', function () {
    $gateway = PaymentGateway::factory()->tap()->create(['is_active' => true]);

    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => false,
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

    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'payment_gateway_id' => $gateway->id,
        'payment_gateway_name' => $gateway->getTranslations('name'),
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $carrier->id,
        'shipping_carrier_name' => $carrier->getTranslations('name'),
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
                'unit_price' => '50.0000',
                'total_price' => '50.0000',
                'product_title' => $product->getTranslations('title'),
                'product_sku' => $product->sku,
                'variant_title' => null,
                'variant_options' => [],
                'weight' => $product->weight,
                'weight_unit' => $product->weight_unit,
            ],
        ],
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '55.0000',
    ]);

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $session->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '55.0000',
        'status' => PaymentSessionStatus::Pending,
        'return_url' => 'https://example.com/success',
    ]);

    $payload = [
        'object' => 'charge',
        'id' => 'chg_tap_1',
        'amount' => 55,
        'currency' => 'USD',
        'status' => 'CAPTURED',
        'reference' => ['transaction' => $paymentSession->id, 'gateway' => 'gw_1', 'payment' => 'pay_1'],
        'transaction' => ['created' => '1700000000000'],
        'source' => ['payment_method' => 'CARD'],
        'card' => ['brand' => 'VISA', 'last_four' => '4242'],
        'metadata' => ['payment_session_id' => $paymentSession->id],
    ];

    $hashString = 'x_id' . $payload['id']
        . 'x_amount55.00'
        . 'x_currencyUSD'
        . 'x_gateway_reference' . $payload['reference']['gateway']
        . 'x_payment_reference' . $payload['reference']['payment']
        . 'x_status' . $payload['status']
        . 'x_created' . $payload['transaction']['created'];

    $response = postJson(
        route('webhooks.payment', ['driver' => 'tap']),
        $payload,
        ['hashstring' => hash_hmac('sha256', $hashString, $gateway->credentials['secret_key'])]
    );

    $response->assertNoContent();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed)
        ->and($session->order_id)->not->toBeNull();

    $order = Order::query()->find($session->order_id);
    expect($order)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);

    $transaction = OrderTransaction::query()->where('order_id', $order->id)->first();
    expect($transaction->payment_method)->toBe('CARD')
        ->and($transaction->payment_method_details)->toBe(['brand' => 'VISA', 'last4' => '4242']);
});

test('returns 403 when Mercado Pago webhook has invalid signature', function () {
    PaymentGateway::factory()->mercadoPago()->create(['is_active' => true]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'mercadopago', 'data.id' => '123', 'type' => 'payment']),
        ['type' => 'payment', 'data' => ['id' => '123']],
        ['x-signature' => 'ts=1700000000,v1=deadbeef', 'x-request-id' => 'req-x']
    );

    $response->assertStatus(403);
});

test('mercado pago webhook with valid signature confirms checkout session and creates order', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create(['is_active' => true]);

    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => false,
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

    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'payment_gateway_id' => $gateway->id,
        'payment_gateway_name' => $gateway->getTranslations('name'),
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $carrier->id,
        'shipping_carrier_name' => $carrier->getTranslations('name'),
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
                'unit_price' => '50.0000',
                'total_price' => '50.0000',
                'product_title' => $product->getTranslations('title'),
                'product_sku' => $product->sku,
                'variant_title' => null,
                'variant_options' => [],
                'weight' => $product->weight,
                'weight_unit' => $product->weight_unit,
            ],
        ],
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '55.0000',
    ]);

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $session->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '55.0000',
        'status' => PaymentSessionStatus::Pending,
        'return_url' => 'https://example.com/success',
    ]);

    $paymentId = '6342936041';

    Http::fake([
        "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
            'id' => (int) $paymentId,
            'status' => 'approved',
            'external_reference' => $paymentSession->id,
            'transaction_amount' => 55,
            'transaction_amount_refunded' => 0,
            'payment_type_id' => 'credit_card',
            'payment_method_id' => 'visa',
            'card' => ['last_four_digits' => '4081'],
        ]),
    ]);

    $ts = '1700000000';
    $requestId = 'req-e2e';
    $manifest = "id:{$paymentId};request-id:{$requestId};ts:{$ts};";
    $signature = hash_hmac('sha256', $manifest, $gateway->credentials['webhook_secret']);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'mercadopago', 'data.id' => $paymentId, 'type' => 'payment']),
        ['type' => 'payment', 'action' => 'payment.updated', 'data' => ['id' => $paymentId]],
        ['x-signature' => "ts={$ts},v1={$signature}", 'x-request-id' => $requestId]
    );

    $response->assertNoContent();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed)
        ->and($session->order_id)->not->toBeNull();

    $order = Order::query()->find($session->order_id);
    expect($order)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);

    $transaction = OrderTransaction::query()->where('order_id', $order->id)->first();
    expect($transaction->gateway_reference)->toBe($paymentId)
        ->and($transaction->payment_method)->toBe('credit_card')
        ->and($transaction->payment_method_details)->toBe(['brand' => 'visa', 'last4' => '4081']);
});

test('returns 403 when Mollie webhook has no payment id', function () {
    PaymentGateway::factory()->mollie()->create(['is_active' => true]);

    $response = postJson(route('webhooks.payment', ['driver' => 'mollie']));

    $response->assertStatus(403);
});

test('returns 403 when Mollie webhook has empty payment id', function () {
    PaymentGateway::factory()->mollie()->create(['is_active' => true]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'mollie']),
        ['id' => '']
    );

    $response->assertStatus(403);
});

test('webhook endpoint is excluded from CSRF verification', function () {
    PaymentGateway::factory()->cod()->create(['is_active' => true]);

    $response = post(route('webhooks.payment', ['driver' => 'cod']));

    $response->assertStatus(403);
});

test('refund webhook creates refund record and updates order refund_total', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'txn_refund_test_123',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'gateway_reference' => 'txn_refund_test_123',
            'refund_amount' => '40.0000',
        ]
    );

    $response->assertNoContent();

    $order->refresh();
    expect($order->refund_total)->toBe('40.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);

    $refund = OrderRefund::query()->where('order_id', $order->id)->first();
    expect($refund)->not->toBeNull()
        ->and($refund->amount)->toBe('40.0000')
        ->and($refund->status)->toBe(RefundStatus::Completed);
});

test('refund webhook is ignored when external refund syncing is disabled', function () {
    $gateway = PaymentGateway::factory()->cod()->create([
        'is_active' => true,
        'sync_external_refunds' => false,
    ]);
    PaymentManager::fake(new MockDriver());

    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'txn_refund_disabled_123',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'gateway_reference' => 'txn_refund_disabled_123',
            'refund_amount' => '40.0000',
        ]
    );

    $response->assertNoContent();

    $order->refresh();
    expect($order->refund_total)->toBe('0.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and(OrderRefund::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('webhook confirms pending checkout session and creates order', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => false,
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

    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'payment_gateway_id' => $gateway->id,
        'payment_gateway_name' => $gateway->getTranslations('name'),
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $carrier->id,
        'shipping_carrier_name' => $carrier->getTranslations('name'),
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
                'unit_price' => '50.0000',
                'total_price' => '50.0000',
                'product_title' => $product->getTranslations('title'),
                'product_sku' => $product->sku,
                'variant_title' => null,
                'variant_options' => [],
                'weight' => $product->weight,
                'weight_unit' => $product->weight_unit,
            ],
        ],
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '55.0000',
    ]);

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $session->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '55.0000',
        'status' => PaymentSessionStatus::Pending,
        'return_url' => 'https://example.com/success',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'payment_session_id' => $paymentSession->id,
            'status' => PaymentStatus::Paid->value,
            'gateway_reference' => 'txn_webhook_123',
        ]
    );

    $response->assertNoContent();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed)
        ->and($session->order_id)->not->toBeNull();

    $order = Order::query()->find($session->order_id);
    expect($order)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);
});

test('webhook ignores non-existent checkout session', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'payment_session_id' => '00000000-0000-0000-0000-000000000000',
            'status' => PaymentStatus::Paid->value,
        ]
    );

    $response->assertNoContent();
});

test('refund webhook resolves order via checkout session when gateway reference is missing', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
    ]);

    $checkoutSession = CheckoutSession::factory()->completed()->create([
        'payment_gateway_id' => $gateway->id,
        'order_id' => $order->id,
    ]);

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkoutSession->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '100.0000',
        'status' => PaymentSessionStatus::Completed,
        'return_url' => 'https://example.com/success',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'payment_session_id' => $paymentSession->id,
            'refund_amount' => '30.0000',
        ]
    );

    $response->assertNoContent();

    $order->refresh();
    expect($order->refund_total)->toBe('30.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
});

test('refund webhook falls back to checkout session when gateway reference does not match any transaction', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'order_original_ref',
    ]);

    $checkoutSession = CheckoutSession::factory()->completed()->create([
        'payment_gateway_id' => $gateway->id,
        'order_id' => $order->id,
    ]);

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkoutSession->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '100.0000',
        'status' => PaymentSessionStatus::Completed,
        'return_url' => 'https://example.com/success',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'gateway_reference' => 'pay_different_ref',
            'payment_session_id' => $paymentSession->id,
            'refund_amount' => '40.0000',
        ]
    );

    $response->assertNoContent();

    $order->refresh();
    expect($order->refund_total)->toBe('40.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);

    $refund = OrderRefund::query()->where('order_id', $order->id)->first();
    expect($refund)->not->toBeNull()
        ->and($refund->amount)->toBe('40.0000')
        ->and($refund->status)->toBe(RefundStatus::Completed);
});

test('refund webhook logs warning when order cannot be resolved', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    Log::spy();

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'gateway_reference' => 'unknown_ref',
            'refund_amount' => '10.0000',
        ]
    );

    $response->assertNoContent();

    Log::shouldHaveReceived('warning')
        ->withArgs(function (string $message, array $context) {
            return str_contains($message, 'Refund webhook received for unknown order')
                && $context['gateway_payment_reference'] === 'unknown_ref';
        });
});

test('refund webhook returns null order when payment session is not found', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    Log::spy();

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'payment_session_id' => '00000000-0000-0000-0000-000000000000',
            'refund_amount' => '10.0000',
        ]
    );

    $response->assertNoContent();

    Log::shouldHaveReceived('warning');
});

test('payment session confirmation is skipped when session is already completed', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $checkoutSession = CheckoutSession::factory()->pending()->create([
        'payment_gateway_id' => $gateway->id,
    ]);

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkoutSession->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '100.0000',
        'status' => PaymentSessionStatus::Completed,
        'completed_at' => now(),
        'return_url' => 'https://example.com/success',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'payment_session_id' => $paymentSession->id,
            'status' => PaymentStatus::Paid->value,
        ]
    );

    $response->assertNoContent();

    $checkoutSession->refresh();
    expect($checkoutSession->status)->toBe(CheckoutSessionStatus::Pending);
});

test('checkout payment confirmation is skipped when checkout session does not exist', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $paymentSession = PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => '00000000-0000-0000-0000-000000000000',
        'purpose' => PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '100.0000',
        'status' => PaymentSessionStatus::Pending,
        'return_url' => 'https://example.com/success',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'payment_session_id' => $paymentSession->id,
            'status' => PaymentStatus::Paid->value,
        ]
    );

    $response->assertNoContent();
});

test('refund webhook for full amount sets refunded status', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    PaymentManager::fake(new MockDriver());

    $order = Order::factory()->create([
        'total' => '50.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '50.0000',
        'gateway_reference' => 'txn_full_refund_test_123',
    ]);

    $response = postJson(
        route('webhooks.payment', ['driver' => 'cod']),
        [
            'gateway_reference' => 'txn_full_refund_test_123',
            'refund_amount' => '50.0000',
        ]
    );

    $response->assertNoContent();

    $order->refresh();
    expect($order->refund_total)->toBe('50.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::Refunded);
});
