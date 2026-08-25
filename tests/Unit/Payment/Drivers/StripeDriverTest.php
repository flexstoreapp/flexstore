<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\PaymentGateway;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\StripeDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

covers(StripeDriver::class);

uses()->group('payment', 'stripe');

function stripeGateway(array $credentials = []): PaymentGateway
{
    return PaymentGateway::factory()->stripe()->create([
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'signing_secret' => 'whsec_test_secret',
            ...$credentials,
        ],
    ]);
}

function buildSignedWebhookRequest(string $payload, string $secret): Request
{
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $header = "t={$timestamp},v1={$signature}";

    $request = Request::create('/webhooks/payment/stripe', 'POST', content: $payload);
    $request->headers->set('Stripe-Signature', $header);

    return $request;
}

test('implements PaymentDriver interface', function () {
    $gateway = PaymentGateway::factory()->stripe()->create();
    $driver = new StripeDriver($gateway);

    expect($driver)->toBeInstanceOf(PaymentDriver::class);
});

test('supports refunds', function () {
    $gateway = PaymentGateway::factory()->stripe()->create();
    $driver = new StripeDriver($gateway);

    expect($driver->supportsRefunds())->toBeTrue();
});

test('verify webhook returns false when webhook secret is empty', function () {
    $gateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Stripe,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
        ],
    ]);

    $driver = new StripeDriver($gateway);
    $request = Request::create('/webhooks/payment/stripe', 'POST');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns false with invalid signature', function () {
    $gateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Stripe,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'signing_secret' => 'whsec_test_123',
        ],
    ]);

    $driver = new StripeDriver($gateway);
    $request = Request::create(route('webhooks.payment', ['driver' => PaymentGatewayDriver::Stripe->value]), 'POST');
    $request->headers->set('Stripe-Signature', 'invalid_signature');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns true with valid signature', function () {
    $secret = 'whsec_test_valid';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_test',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_test', 'object' => 'payment_intent']],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);

    expect($driver->verifyWebhook($request))->toBeTrue();
});

test('verify webhook returns true when multiple v1 signatures and one matches', function () {
    $secret = 'whsec_test_multi';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode(['id' => 'evt_test', 'object' => 'event', 'type' => 'ping', 'data' => ['object' => []]]);
    $timestamp = time();
    $validSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $header = "t={$timestamp},v1=invalid_old_signature,v1={$validSignature}";

    $request = Request::create('/webhooks/payment/stripe', 'POST', content: $payload);
    $request->headers->set('Stripe-Signature', $header);

    expect($driver->verifyWebhook($request))->toBeTrue();
});

test('verify webhook returns false when timestamp is expired', function () {
    $secret = 'whsec_test_expired';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode(['id' => 'evt_test', 'object' => 'event', 'type' => 'ping', 'data' => ['object' => []]]);
    $timestamp = time() - 400;
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $header = "t={$timestamp},v1={$signature}";

    $request = Request::create('/webhooks/payment/stripe', 'POST', content: $payload);
    $request->headers->set('Stripe-Signature', $header);

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify payment returns current status when gateway reference is null', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    $verification = $driver->verifyPayment(null, PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid)
        ->and($verification->gatewayReference)->toBeNull();
});

test('refund returns failed when no gateway reference', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: null,
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->amount)->toBe('50.0000')
        ->and($refund->failureReason)->toBe('No gateway reference found for this order.');
});

test('parses checkout.session.completed webhook', function () {
    $secret = 'whsec_test_checkout';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_checkout',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_123',
                'object' => 'checkout.session',
                'payment_intent' => 'pi_from_checkout',
                'metadata' => ['payment_session_id' => 'session_abc'],
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('checkout.session.completed')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('session_abc')
        ->and($event->gatewayPaymentReference)->toBe('pi_from_checkout');
});

test('parses payment_intent.succeeded webhook', function () {
    $secret = 'whsec_test_succeeded';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_succeeded',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_succeeded_123',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount_received' => 5000,
                'metadata' => ['payment_session_id' => 'session_def'],
                'latest_charge' => null,
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment_intent.succeeded')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('session_def')
        ->and($event->gatewayPaymentReference)->toBe('pi_succeeded_123')
        ->and($event->payload['amount'])->toBe('50.0000');
});

test('extracts payment method details from payment_intent.succeeded webhook', function () {
    $secret = 'whsec_test_method';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_with_charge',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_method_123',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount_received' => 5000,
                'metadata' => ['payment_session_id' => 'session_xyz'],
                'latest_charge' => [
                    'id' => 'ch_test_123',
                    'object' => 'charge',
                    'payment_method_details' => [
                        'type' => 'card',
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->paymentMethod)->toBe('card')
        ->and($event->paymentMethodDetails)->toBe(['brand' => 'visa', 'last4' => '4242']);
});

test('parses payment_intent.payment_failed webhook', function () {
    $secret = 'whsec_test_failed';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_failed',
        'object' => 'event',
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_failed_123',
                'object' => 'payment_intent',
                'status' => 'requires_payment_method',
                'amount_received' => 0,
                'last_payment_error' => ['message' => 'Card declined'],
                'metadata' => ['payment_session_id' => 'session_ghi'],
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment_intent.payment_failed')
        ->and($event->status)->toBe(PaymentStatus::Failed)
        ->and($event->paymentSessionId)->toBe('session_ghi')
        ->and($event->gatewayPaymentReference)->toBe('pi_failed_123')
        ->and($event->payload['error'])->toBe('Card declined');
});

test('parses charge.refunded webhook for full refund', function () {
    $secret = 'whsec_test_refund';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_refund',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_refund_123',
                'object' => 'charge',
                'payment_intent' => 'pi_original',
                'refunded' => true,
                'amount_refunded' => 10000,
                'refunds' => [
                    'object' => 'list',
                    'data' => [
                        ['id' => 're_stripe_full1', 'object' => 'refund', 'amount' => 10000],
                    ],
                ],
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('charge.refunded')
        ->and($event->status)->toBe(PaymentStatus::Refunded)
        ->and($event->gatewayPaymentReference)->toBe('pi_original')
        ->and($event->cumulativeRefundTotal)->toBe('100.0000')
        ->and($event->gatewayRefundReference)->toBe('re_stripe_full1')
        ->and($event->payload['charge_id'])->toBe('ch_refund_123')
        ->and($event->payload['amount_refunded'])->toBe('100.0000');
});

test('parses charge.refunded webhook for partial refund', function () {
    $secret = 'whsec_test_partial';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_partial',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_partial_123',
                'object' => 'charge',
                'payment_intent' => 'pi_partial_original',
                'refunded' => false,
                'amount_refunded' => 2500,
                'refunds' => [
                    'object' => 'list',
                    'data' => [
                        ['id' => 're_stripe_partial1', 'object' => 'refund', 'amount' => 2500],
                    ],
                ],
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('charge.refunded')
        ->and($event->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($event->cumulativeRefundTotal)->toBe('25.0000')
        ->and($event->gatewayRefundReference)->toBe('re_stripe_partial1');
});

test('converts zero-decimal currency amount in payment_intent.succeeded webhook', function () {
    $secret = 'whsec_test_jpy';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    App\Models\Currency::query()->firstOrCreate(
        ['code' => 'JPY'],
        ['symbol' => '¥', 'exchange_rate' => 1, 'decimal_places' => 0, 'is_active' => true, 'symbol_position' => 'before', 'thousands_separator' => ',', 'decimal_separator' => '.'],
    );

    $payload = json_encode([
        'id' => 'evt_jpy',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_jpy_123',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount_received' => 1000,
                'currency' => 'jpy',
                'metadata' => ['payment_session_id' => 'session_jpy'],
                'latest_charge' => null,
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->payload['amount'])->toBe('1000.0000');
});

test('converts zero-decimal currency amount in charge.refunded webhook', function () {
    $secret = 'whsec_test_jpy_refund';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    App\Models\Currency::query()->firstOrCreate(
        ['code' => 'JPY'],
        ['symbol' => '¥', 'exchange_rate' => 1, 'decimal_places' => 0, 'is_active' => true, 'symbol_position' => 'before', 'thousands_separator' => ',', 'decimal_separator' => '.'],
    );

    $payload = json_encode([
        'id' => 'evt_jpy_refund',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_jpy_123',
                'object' => 'charge',
                'payment_intent' => 'pi_jpy_original',
                'refunded' => true,
                'amount_refunded' => 500,
                'currency' => 'jpy',
                'refunds' => [
                    'object' => 'list',
                    'data' => [
                        ['id' => 're_jpy_1', 'object' => 'refund', 'amount' => 500],
                    ],
                ],
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBe('500.0000')
        ->and($event->payload['amount_refunded'])->toBe('500.0000');
});

function stripeCreateSession(string $amount = '50.00', array $meta = ['payment_session_id' => 'sess_1']): CreateSession
{
    return new CreateSession(
        internalReference: 'sess_1',
        amount: $amount,
        currencyCode: 'USD',
        redirectUrls: new RedirectUrls(
            returnUrl: 'https://example.com/return',
            cancelUrl: 'https://example.com/cancel',
            failureUrl: 'https://example.com/fail',
        ),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        customerEmail: 'buyer@example.com',
        description: 'Order #1',
        metadata: $meta,
    );
}

test('createSession in embedded mode creates payment intent', function () {
    $gateway = stripeGateway(['checkout_mode' => CheckoutMode::Embedded->value]);
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents' => Http::response([
            'id' => 'pi_test_123',
            'client_secret' => 'pi_test_123_secret',
        ], 200),
    ]);

    $result = $driver->createSession(stripeCreateSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->gatewayReference)->toBe('pi_test_123')
        ->and($result->payload)->toMatchArray([
            'payment_intent_id' => 'pi_test_123',
            'return_url' => 'https://example.com/return',
            'client_secret' => 'pi_test_123_secret',
        ]);
});

test('createSession in embedded mode returns failed result when stripe returns error', function () {
    $gateway = stripeGateway(['checkout_mode' => CheckoutMode::Embedded->value]);
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents' => Http::response([
            'error' => ['message' => 'Invalid amount'],
        ], 400),
    ]);

    $result = $driver->createSession(stripeCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Invalid amount')
        ->and($result->redirectUrl)->toBe('https://example.com/fail');
});

test('createSession in embedded mode returns failed result when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['credentials' => []]);
    $driver = new StripeDriver($gateway);

    $result = $driver->createSession(stripeCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Stripe secret key is not configured.');
});

test('createSession in hosted mode creates checkout session', function () {
    $gateway = stripeGateway(['checkout_mode' => CheckoutMode::Hosted->value]);
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.com/pay/cs_test_123',
        ], 200),
    ]);

    $result = $driver->createSession(stripeCreateSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->gatewayReference)->toBe('cs_test_123')
        ->and($result->redirectUrl)->toBe('https://checkout.stripe.com/pay/cs_test_123')
        ->and($result->payload)->toBe(['session_id' => 'cs_test_123']);
});

test('createSession in hosted mode returns failed result when stripe returns error', function () {
    $gateway = stripeGateway(['checkout_mode' => CheckoutMode::Hosted->value]);
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/checkout/sessions' => Http::response([
            'error' => ['message' => 'Bad currency'],
        ], 400),
    ]);

    $result = $driver->createSession(stripeCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Bad currency');
});

test('createSession in hosted mode falls back to failure url when stripe session has no url', function () {
    $gateway = stripeGateway(['checkout_mode' => CheckoutMode::Hosted->value]);
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_nourl',
        ], 200),
    ]);

    $result = $driver->createSession(stripeCreateSession());

    expect($result->redirectUrl)->toBe('https://example.com/fail');
});

test('createSession in hosted mode returns failed result when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->stripe()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Hosted->value],
    ]);
    $driver = new StripeDriver($gateway);

    $result = $driver->createSession(stripeCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Stripe secret key is not configured.');
});

test('verifyPayment returns paid status when stripe reports succeeded', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_test_paid*' => Http::response([
            'id' => 'pi_test_paid',
            'status' => 'succeeded',
        ], 200),
    ]);

    $verification = $driver->verifyPayment('pi_test_paid', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('pi_test_paid');
});

test('verifyPayment returns canceled status when stripe reports canceled', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_canceled*' => Http::response([
            'id' => 'pi_canceled',
            'status' => 'canceled',
        ], 200),
    ]);

    $verification = $driver->verifyPayment('pi_canceled', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Canceled);
});

test('verifyPayment returns failed when requires_payment_method with last_payment_error', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_reqpm*' => Http::response([
            'id' => 'pi_reqpm',
            'status' => 'requires_payment_method',
            'last_payment_error' => ['message' => 'Declined'],
        ], 200),
    ]);

    $verification = $driver->verifyPayment('pi_reqpm', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Failed);
});

test('verifyPayment keeps current status when requires_payment_method without error', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_pending*' => Http::response([
            'id' => 'pi_pending',
            'status' => 'requires_payment_method',
        ], 200),
    ]);

    $verification = $driver->verifyPayment('pi_pending', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('verifyPayment resolves payment intent from checkout session reference', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/checkout/sessions/cs_abc*' => Http::response([
            'id' => 'cs_abc',
            'payment_intent' => 'pi_from_cs',
        ], 200),
        'api.stripe.com/v1/payment_intents/pi_from_cs*' => Http::response([
            'id' => 'pi_from_cs',
            'status' => 'succeeded',
        ], 200),
    ]);

    $verification = $driver->verifyPayment('cs_abc', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('pi_from_cs');
});

test('verifyPayment keeps current status when checkout session lookup fails', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/checkout/sessions/cs_missing*' => Http::response(['error' => 'not found'], 404),
    ]);

    $verification = $driver->verifyPayment('cs_missing', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('verifyPayment keeps current status when payment intent lookup fails', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_err*' => Http::response(['error' => 'not found'], 500),
    ]);

    $verification = $driver->verifyPayment('pi_err', PaymentStatus::Paid);

    expect($verification->status)->toBe(PaymentStatus::Paid);
});

test('verifyPayment returns current status when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['credentials' => []]);
    $driver = new StripeDriver($gateway);

    $verification = $driver->verifyPayment('pi_something', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('verifyPayment extracts payment method details from latest charge', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_card*' => Http::response([
            'id' => 'pi_card',
            'status' => 'succeeded',
            'latest_charge' => [
                'id' => 'ch_card_1',
                'payment_method_details' => [
                    'type' => 'card',
                    'card' => ['brand' => 'visa', 'last4' => '4242'],
                ],
            ],
        ], 200),
    ]);

    $verification = $driver->verifyPayment('pi_card', PaymentStatus::Unpaid);

    expect($verification->paymentMethod)->toBe('card')
        ->and($verification->paymentMethodDetails)->toBe(['brand' => 'visa', 'last4' => '4242']);
});

test('extract payment method details handles non-card types', function () {
    $secret = 'whsec_test_nocard';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_bank',
                'amount_received' => 2500,
                'metadata' => ['payment_session_id' => 'sess_b'],
                'latest_charge' => [
                    'id' => 'ch_bank_1',
                    'payment_method_details' => [
                        'type' => 'bank_transfer',
                    ],
                ],
            ],
        ],
    ]);

    $event = $driver->parseWebhook(buildSignedWebhookRequest($payload, $secret));

    expect($event->paymentMethod)->toBe('bank_transfer')
        ->and($event->paymentMethodDetails)->toBeNull();
});

test('extract payment method details returns null when method details are missing', function () {
    $secret = 'whsec_test_nomethod';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_nomethod',
                'amount_received' => 100,
                'metadata' => ['payment_session_id' => 'sess_c'],
                'latest_charge' => ['id' => 'ch_nomethod'],
            ],
        ],
    ]);

    $event = $driver->parseWebhook(buildSignedWebhookRequest($payload, $secret));

    expect($event->paymentMethod)->toBeNull()
        ->and($event->paymentMethodDetails)->toBeNull();
});

test('refund succeeds and returns completed refund result', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_1',
            'amount' => 2500,
            'currency' => 'usd',
            'status' => 'succeeded',
        ], 200),
    ]);

    $result = $driver->refund(new RefundPayment(
        amount: '25.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_test',
        reason: 'duplicate',
        idempotencyKey: 'refund_key_1',
    ));

    expect($result->status)->toBe(RefundStatus::Completed)
        ->and($result->amount)->toBe('25.0000')
        ->and($result->gatewayReference)->toBe('re_1')
        ->and($result->payload)->toBe(['refund_id' => 're_1']);

    Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'refund_key_1')
        && $request['payment_intent'] === 'pi_test'
        && $request['reason'] === 'duplicate');
});

test('refund maps pending status', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_pending',
            'amount' => 1000,
            'currency' => 'usd',
            'status' => 'pending',
        ], 200),
    ]);

    $result = $driver->refund(new RefundPayment(
        amount: '10.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_pending',
    ));

    expect($result->status)->toBe(RefundStatus::Pending);
});

test('refund maps unknown status to failed', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_unknown',
            'amount' => 500,
            'currency' => 'usd',
            'status' => 'something_else',
        ], 200),
    ]);

    $result = $driver->refund(new RefundPayment(
        amount: '5.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_1',
    ));

    expect($result->status)->toBe(RefundStatus::Failed);
});

test('refund returns failed result when stripe returns an error', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response([
            'error' => ['message' => 'Charge already refunded'],
        ], 400),
    ]);

    $result = $driver->refund(new RefundPayment(
        amount: '5.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_dupe',
    ));

    expect($result->status)->toBe(RefundStatus::Failed)
        ->and($result->failureReason)->toBe('Charge already refunded');
});

test('refund returns failed result when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['credentials' => []]);
    $driver = new StripeDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '5.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_1',
    ));

    expect($result->status)->toBe(RefundStatus::Failed)
        ->and($result->failureReason)->toBe('Stripe secret key is not configured.');
});

test('refund maps fraudulent reason', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_fraud',
            'amount' => 1000,
            'currency' => 'usd',
            'status' => 'succeeded',
        ], 200),
    ]);

    $driver->refund(new RefundPayment(
        amount: '10.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_fraud',
        reason: 'fraudulent',
    ));

    Http::assertSent(fn ($request) => $request['reason'] === 'fraudulent');
});

test('refund falls back to requested_by_customer reason', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_other',
            'amount' => 1000,
            'currency' => 'usd',
            'status' => 'succeeded',
        ], 200),
    ]);

    $driver->refund(new RefundPayment(
        amount: '10.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_other',
        reason: 'something-else',
    ));

    Http::assertSent(fn ($request) => $request['reason'] === 'requested_by_customer');
});

test('is manual returns false', function () {
    $gateway = stripeGateway();

    expect((new StripeDriver($gateway))->isManual())->toBeFalse();
});

test('test connection returns true when stripe payment intents request succeeds', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake(['api.stripe.com/v1/payment_intents*' => Http::response(['object' => 'list', 'data' => []], 200)]);

    expect($driver->testConnection())->toBeTrue();
});

test('test connection returns true when stripe key is valid but restricted', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake(['api.stripe.com/v1/payment_intents*' => Http::response(['error' => ['type' => 'invalid_request_error']], 403)]);

    expect($driver->testConnection())->toBeTrue();
});

test('test connection returns false when stripe rejects the credentials', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);

    Http::fake(['api.stripe.com/v1/payment_intents*' => Http::response(['error' => 'invalid'], 401)]);

    expect($driver->testConnection())->toBeFalse();
});

test('test connection returns false when stripe secret key is missing', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['credentials' => []]);

    expect((new StripeDriver($gateway))->testConnection())->toBeFalse();
});

test('verify webhook returns false when signature header is missing', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);
    $request = Request::create('/webhooks/payment/stripe', 'POST');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns false when timestamp is missing', function () {
    $gateway = stripeGateway();
    $driver = new StripeDriver($gateway);
    $request = Request::create('/webhooks/payment/stripe', 'POST');
    $request->headers->set('Stripe-Signature', 'v1=abc');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('parses unknown webhook event type', function () {
    $secret = 'whsec_test_unknown';
    $gateway = stripeGateway(['signing_secret' => $secret]);
    $driver = new StripeDriver($gateway);

    $payload = json_encode([
        'id' => 'evt_unknown',
        'object' => 'event',
        'type' => 'customer.created',
        'data' => [
            'object' => [
                'id' => 'cus_test',
                'object' => 'customer',
            ],
        ],
    ]);

    $request = buildSignedWebhookRequest($payload, $secret);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('customer.created')
        ->and($event->status)->toBeNull();
});
