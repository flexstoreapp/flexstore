<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\MollieDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

covers(MollieDriver::class);

uses()->group('payment', 'mollie');

test('implements PaymentDriver interface', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    expect($driver)->toBeInstanceOf(PaymentDriver::class);
});

test('supports refunds', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    expect($driver->supportsRefunds())->toBeTrue();
});

test('parses webhook for paid payment', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_test123*' => Http::response([
            'id' => 'tr_test123',
            'status' => 'paid',
            'amount' => ['currency' => 'EUR', 'value' => '50.0000'],
            'metadata' => ['payment_session_id' => 'cs_1'],
        ]),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_test123']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.paid')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('cs_1')
        ->and($event->gatewayPaymentReference)->toBe('tr_test123')
        ->and($event->cumulativeRefundTotal)->toBeNull()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('parses webhook for partially refunded payment', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_test456*' => Http::response([
            'id' => 'tr_test456',
            'status' => 'paid',
            'amount' => ['currency' => 'EUR', 'value' => '100.0000'],
            'amountRefunded' => ['currency' => 'EUR', 'value' => '25.0000'],
            'metadata' => ['payment_session_id' => 'cs_2'],
            '_embedded' => ['refunds' => [
                ['id' => 're_partialRefund1', 'amount' => ['value' => '25.0000']],
            ]],
        ]),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_test456']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.refunded')
        ->and($event->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($event->gatewayPaymentReference)->toBe('tr_test456')
        ->and($event->payload['amount_refunded'])->toBe('25.0000')
        ->and($event->cumulativeRefundTotal)->toBe('25.0000')
        ->and($event->gatewayRefundReference)->toBe('re_partialRefund1')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('parses webhook for fully refunded payment', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_test789*' => Http::response([
            'id' => 'tr_test789',
            'status' => 'paid',
            'amount' => ['currency' => 'EUR', 'value' => '50.0000'],
            'amountRefunded' => ['currency' => 'EUR', 'value' => '50.0000'],
            'metadata' => ['payment_session_id' => 'cs_3'],
            '_embedded' => ['refunds' => [
                ['id' => 're_fullRefund1', 'amount' => ['value' => '50.0000']],
            ]],
        ]),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_test789']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.refunded')
        ->and($event->status)->toBe(PaymentStatus::Refunded)
        ->and($event->gatewayPaymentReference)->toBe('tr_test789')
        ->and($event->payload['amount_refunded'])->toBe('50.0000')
        ->and($event->cumulativeRefundTotal)->toBe('50.0000')
        ->and($event->gatewayRefundReference)->toBe('re_fullRefund1')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('parses webhook for failed payment', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_fail*' => Http::response([
            'id' => 'tr_fail',
            'status' => 'failed',
            'metadata' => ['payment_session_id' => 'cs_4'],
        ]),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_fail']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.failed')
        ->and($event->status)->toBe(PaymentStatus::Failed)
        ->and($event->paymentSessionId)->toBe('cs_4');
});

test('parses webhook for canceled payment', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_cancel*' => Http::response([
            'id' => 'tr_cancel',
            'status' => 'canceled',
            'metadata' => ['payment_session_id' => 'cs_5'],
        ]),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_cancel']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.canceled')
        ->and($event->status)->toBe(PaymentStatus::Canceled)
        ->and($event->paymentSessionId)->toBe('cs_5');
});

test('parses webhook for canceled refund with zero amountRefunded', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_canceled_refund*' => Http::response([
            'id' => 'tr_canceled_refund',
            'status' => 'paid',
            'amount' => ['currency' => 'EUR', 'value' => '100.00'],
            'amountRefunded' => ['currency' => 'EUR', 'value' => '0.00'],
            'metadata' => ['payment_session_id' => 'cs_6'],
        ]),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_canceled_refund']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.refunded')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->gatewayPaymentReference)->toBe('tr_canceled_refund')
        ->and($event->cumulativeRefundTotal)->toBeNull()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('parses webhook for paid payment without amountRefunded field', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_fresh*' => Http::response([
            'id' => 'tr_fresh',
            'status' => 'paid',
            'amount' => ['currency' => 'EUR', 'value' => '50.0000'],
            'metadata' => ['payment_session_id' => 'cs_7'],
        ]),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_fresh']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.paid')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->cumulativeRefundTotal)->toBeNull()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('handles webhook when Mollie API fails', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake([
        'api.mollie.com/v2/payments/tr_error*' => Http::response([], 500),
    ]);

    $request = Request::create('/', 'POST', ['id' => 'tr_error']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.unknown')
        ->and($event->status)->toBeNull();
});

test('verify payment returns current status when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();

    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment(null, PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid)
        ->and($verification->gatewayReference)->toBeNull();
});

test('verify payment returns paid status', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_verify_paid' => Http::response([
            'id' => 'tr_verify_paid',
            'status' => 'paid',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment('tr_verify_paid', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('tr_verify_paid');
});

test('verify payment returns failed status for failed mollie payment', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_verify_failed' => Http::response([
            'id' => 'tr_verify_failed',
            'status' => 'failed',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment('tr_verify_failed', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Failed)
        ->and($verification->gatewayReference)->toBe('tr_verify_failed');
});

test('verify payment returns failed status for expired mollie payment', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_verify_expired' => Http::response([
            'id' => 'tr_verify_expired',
            'status' => 'expired',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment('tr_verify_expired', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Failed)
        ->and($verification->gatewayReference)->toBe('tr_verify_expired');
});

test('verify payment returns canceled status for canceled mollie payment', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_verify_canceled' => Http::response([
            'id' => 'tr_verify_canceled',
            'status' => 'canceled',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment('tr_verify_canceled', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Canceled)
        ->and($verification->gatewayReference)->toBe('tr_verify_canceled');
});

test('verify payment returns current status for unknown mollie status', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_verify_open' => Http::response([
            'id' => 'tr_verify_open',
            'status' => 'open',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment('tr_verify_open', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns current status when API fails', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_verify_fail' => Http::response([], 500),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment('tr_verify_fail', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('refund fails when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: null,
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('No gateway reference found for this order.');
});

test('refund succeeds', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments/tr_refund_ok/refunds' => Http::response([
            'id' => 're_test_123',
            'status' => 'refunded',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '25.0000',
        currencyCode: 'USD',
        gatewayReference: 'tr_refund_ok',
        reason: 'Customer request',
    ));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->amount)->toBe('25.0000')
        ->and($refund->gatewayReference)->toBe('re_test_123')
        ->and($refund->payload['refund_id'])->toBe('re_test_123')
        ->and($refund->payload['mollie_payment_id'])->toBe('tr_refund_ok');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/refunds')
            && $request['amount']['value'] === '25.00'
            && $request['description'] === 'Customer request';
    });
});

test('refund succeeds with pending status', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments/tr_refund_pending/refunds' => Http::response([
            'id' => 're_test_pending',
            'status' => 'pending',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '30.0000',
        currencyCode: 'USD',
        gatewayReference: 'tr_refund_pending',
    ));

    expect($refund->status)->toBe(RefundStatus::Pending);
});

test('refund without reason omits description', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments/tr_refund_noreason/refunds' => Http::response([
            'id' => 're_test_noreason',
            'status' => 'refunded',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);
    $driver->refund(new RefundPayment(
        amount: '10.0000',
        currencyCode: 'USD',
        gatewayReference: 'tr_refund_noreason',
    ));

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/refunds')
            && ! array_key_exists('description', $request->data());
    });
});

test('refund fails when API returns error', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments/tr_refund_fail/refunds' => Http::response([
            'detail' => 'Insufficient balance.',
        ], 422),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: 'tr_refund_fail',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('Insufficient balance.');
});

test('refund fails for unknown refund status', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments/tr_refund_unknown/refunds' => Http::response([
            'id' => 're_test_unknown',
            'status' => 'failed',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: 'tr_refund_unknown',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed);
});

test('creates hosted session', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_session_hosted',
            'status' => 'open',
            '_links' => [
                'checkout' => ['href' => 'https://www.mollie.com/checkout/test123'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_hosted_key',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '100.0000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        shippingAddress: $checkoutSession->shipping_address,
        billingAddress: $checkoutSession->billing_address,
    ));

    expect($session->status)->toBe(PaymentStatus::Unpaid)
        ->and($session->redirectUrl)->toBe('https://www.mollie.com/checkout/test123')
        ->and($session->gatewayReference)->toBe('tr_session_hosted')
        ->and($session->payload['mollie_payment_id'])->toBe('tr_session_hosted');
});

test('hosted session fails when checkout url is missing', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_no_checkout',
            'status' => 'open',
            '_links' => [],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_hosted_key',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '50.0000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
    ));

    expect($session->status)->toBe(PaymentStatus::Failed)
        ->and($session->failureReason)->toBe('Failed to create Mollie payment.');
});

test('hosted session fails when API returns error', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'detail' => 'Invalid API key.',
        ], 401),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_invalid_key',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '50.0000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
    ));

    expect($session->status)->toBe(PaymentStatus::Failed)
        ->and($session->failureReason)->toBe('Invalid API key.');
});

test('creates embedded session', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_session_embedded',
            'status' => 'open',
            '_links' => [
                'checkout' => ['href' => 'https://www.mollie.com/checkout/embedded123'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_embedded_key',
            'profile_id' => 'pfl_test_123',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '80.0000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: route('checkout.create')),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        providerOptions: ['card_token' => 'tkn_test_embedded'],
    ));

    expect($session->status)->toBe(PaymentStatus::Unpaid)
        ->and($session->redirectUrl)->toBe(route('checkout.create'))
        ->and($session->gatewayReference)->toBe('tr_session_embedded')
        ->and($session->payload['mollie_payment_id'])->toBe('tr_session_embedded')
        ->and($session->payload['profile_id'])->toBe('pfl_test_123')
        ->and($session->payload)->toHaveKey('return_url');

    Http::assertSent(function ($request) {
        return $request['cardToken'] === 'tkn_test_embedded';
    });
});

test('embedded session fails without card token', function () {
    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_embedded_key',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '80.0000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
    ));

    expect($session->status)->toBe(PaymentStatus::Failed)
        ->and($session->failureReason)->toBe(__('Card token is required for embedded checkout.'));
});

test('creates embedded session with card token', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_session_card',
            'status' => 'paid',
            '_links' => [],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $checkoutSession = CheckoutSession::factory()->create(['total' => '50.0000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        providerOptions: ['card_token' => 'tkn_test_abc'],
    ));

    expect($session->status)->toBe(PaymentStatus::Paid);

    Http::assertSent(function ($request) {
        return $request['cardToken'] === 'tkn_test_abc';
    });
});

test('embedded session defaults when checkout_mode not set', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_session_default',
            'status' => 'open',
            '_links' => [],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_default_key',
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '50.0000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: route('checkout.create')),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        providerOptions: ['card_token' => 'tkn_test_default'],
    ));

    expect($session->redirectUrl)->toBe(route('checkout.create'));
});

test('embedded session fails when API returns error', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'detail' => 'Amount too low.',
        ], 422),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();

    $checkoutSession = CheckoutSession::factory()->create(['total' => '0.5000']);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        providerOptions: ['card_token' => 'tkn_test_error'],
    ));

    expect($session->status)->toBe(PaymentStatus::Failed)
        ->and($session->failureReason)->toBe('Amount too low.');
});

test('hosted session includes billing and shipping address', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_addr_hosted',
            'status' => 'open',
            '_links' => [
                'checkout' => ['href' => 'https://www.mollie.com/checkout/addr123'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_addr_key',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create([
        'total' => '100.0000',
        'customer_email' => 'john@example.com',
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => 'Keizersgracht 313',
            'address_line_2' => 'Apt 4',
            'postal_code' => '1016 EE',
            'city' => 'Amsterdam',
            'state' => 'Noord-Holland',
            'country_code' => 'NL',
            'phone' => '+31612345678',
        ],
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => 'Herengracht 100',
            'address_line_2' => null,
            'postal_code' => '1015 BS',
            'city' => 'Amsterdam',
            'state' => 'Noord-Holland',
            'country_code' => 'NL',
            'phone' => null,
        ],
    ]);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        shippingAddress: $checkoutSession->shipping_address,
        billingAddress: $checkoutSession->billing_address,
    ));

    expect($session->status)->toBe(PaymentStatus::Unpaid);

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['billingAddress']['givenName'] === 'John'
            && $data['billingAddress']['familyName'] === 'Doe'
            && $data['billingAddress']['streetAndNumber'] === 'Keizersgracht 313'
            && $data['billingAddress']['streetAdditional'] === 'Apt 4'
            && $data['billingAddress']['postalCode'] === '1016 EE'
            && $data['billingAddress']['city'] === 'Amsterdam'
            && $data['billingAddress']['country'] === 'NL'
            && $data['billingAddress']['email'] === 'john@example.com'
            && $data['billingAddress']['phone'] === '+31612345678'
            && $data['shippingAddress']['givenName'] === 'Jane'
            && $data['shippingAddress']['familyName'] === 'Doe'
            && $data['shippingAddress']['streetAndNumber'] === 'Herengracht 100'
            && ! array_key_exists('streetAdditional', $data['shippingAddress'])
            && ! array_key_exists('phone', $data['shippingAddress']);
    });
});

test('embedded session includes billing and shipping address', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_addr_embedded',
            'status' => 'open',
            '_links' => [
                'checkout' => ['href' => 'https://www.mollie.com/checkout/addr456'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_addr_key',
            'profile_id' => 'pfl_addr_test',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create([
        'total' => '80.0000',
        'customer_email' => 'jane@example.com',
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => 'Friedrichstraße 123',
            'city' => 'Berlin',
            'postal_code' => '10117',
            'country_code' => 'DE',
        ],
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => 'Alexanderplatz 1',
            'city' => 'Berlin',
            'postal_code' => '10178',
            'country_code' => 'DE',
        ],
    ]);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: route('checkout.create')),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        shippingAddress: $checkoutSession->shipping_address,
        billingAddress: $checkoutSession->billing_address,
        providerOptions: ['card_token' => 'tkn_addr_test'],
    ));

    expect($session->status)->toBe(PaymentStatus::Unpaid);

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['cardToken'] === 'tkn_addr_test'
            && $data['billingAddress']['givenName'] === 'Jane'
            && $data['billingAddress']['familyName'] === 'Smith'
            && $data['billingAddress']['streetAndNumber'] === 'Friedrichstraße 123'
            && $data['billingAddress']['email'] === 'jane@example.com'
            && $data['shippingAddress']['streetAndNumber'] === 'Alexanderplatz 1';
    });
});

test('session without separate billing address uses shipping address for billing', function () {
    Setting::setValue('base_currency', 'EUR');

    Http::fake([
        'api.mollie.com/v2/payments' => Http::response([
            'id' => 'tr_no_billing',
            'status' => 'open',
            '_links' => [
                'checkout' => ['href' => 'https://www.mollie.com/checkout/nobilling'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => [
            'api_key' => 'test_no_billing_key',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create([
        'total' => '50.0000',
        'billing_address' => null,
        'different_billing_address' => false,
    ]);

    $driver = new MollieDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
        shippingAddress: $checkoutSession->shipping_address,
        billingAddress: $checkoutSession->shipping_address,
    ));

    expect($session->status)->toBe(PaymentStatus::Unpaid);

    Http::assertSent(function ($request) {
        $data = $request->data();

        return $data['billingAddress']['givenName'] === $data['shippingAddress']['givenName']
            && $data['billingAddress']['familyName'] === $data['shippingAddress']['familyName'];
    });
});

test('verify payment extracts card payment method details', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_card_test' => Http::response([
            'id' => 'tr_card_test',
            'status' => 'paid',
            'method' => 'creditcard',
            'details' => [
                'cardHolder' => 'John Doe',
                'cardNumber' => '6787',
                'cardLabel' => 'Visa',
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);
    $verification = $driver->verifyPayment('tr_card_test', PaymentStatus::Unpaid);

    expect($verification->paymentMethod)->toBe('creditcard')
        ->and($verification->paymentMethodDetails)->toBe(['brand' => 'Visa', 'last4' => '6787']);
});

test('webhook extracts payment method details', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_webhook_card*' => Http::response([
            'id' => 'tr_webhook_card',
            'status' => 'paid',
            'method' => 'ideal',
            'metadata' => ['payment_session_id' => 'cs_1'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    $request = Request::create('/', 'POST', ['id' => 'tr_webhook_card']);
    $event = $driver->parseWebhook($request);

    expect($event->paymentMethod)->toBe('ideal')
        ->and($event->paymentMethodDetails)->toBeNull();
});

test('is manual returns false', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();

    expect((new MollieDriver($gateway))->isManual())->toBeFalse();
});

test('verify payment returns current status when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->mollie()->create(['credentials' => []]);
    $driver = new MollieDriver($gateway);

    expect($driver->verifyPayment('tr_x', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('parseWebhook returns unknown event when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->mollie()->create(['credentials' => []]);
    $driver = new MollieDriver($gateway);

    $request = Request::create('/', 'POST', ['id' => 'tr_unknown']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.unknown');
});

test('refund sends idempotency key header when provided', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_idem/refunds' => Http::response([
            'id' => 're_idem',
            'status' => 'refunded',
        ], 200),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '25.00',
        currencyCode: 'USD',
        gatewayReference: 'tr_idem',
        idempotencyKey: 'idem-key',
    ));

    expect($result->status)->toBe(RefundStatus::Completed);

    Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'idem-key'));
});

test('refund returns failed when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->mollie()->create(['credentials' => []]);
    $driver = new MollieDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'USD',
        gatewayReference: 'tr_boom',
    ));

    expect($result->status)->toBe(RefundStatus::Failed)
        ->and($result->failureReason)->toBe('Mollie API key is not configured.');
});

test('createEmbeddedSession returns failed result when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Embedded->value],
    ]);

    $session = new CreateSession(
        internalReference: 'sess_emb',
        amount: '10.00',
        currencyCode: 'USD',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/r', failureUrl: 'https://example.com/f'),
        callbackUrls: new CallbackUrls(),
        providerOptions: ['card_token' => 'tok_test'],
    );

    $result = (new MollieDriver($gateway))->createSession($session);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Mollie API key is not configured.');
});

test('createHostedSession returns failed result when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->mollie()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Hosted->value],
    ]);

    $session = new CreateSession(
        internalReference: 'sess_hosted',
        amount: '10.00',
        currencyCode: 'USD',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/r', failureUrl: 'https://example.com/f'),
        callbackUrls: new CallbackUrls(),
    );

    $result = (new MollieDriver($gateway))->createSession($session);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Mollie API key is not configured.');
});

test('parseWebhook returns null status for unknown mollie status', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_unknown_status*' => Http::response([
            'id' => 'tr_unknown_status',
            'status' => 'pending',
        ], 200),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    $request = Request::create('/', 'POST', ['id' => 'tr_unknown_status']);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.pending')
        ->and($event->status)->toBeNull();
});

test('webhook extracts card details', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_card_full*' => Http::response([
            'id' => 'tr_card_full',
            'status' => 'paid',
            'method' => 'creditcard',
            'details' => [
                'cardHolder' => 'John Doe',
                'cardLabel' => 'Visa',
                'cardNumber' => 'XXXX XXXX XXXX 4242',
            ],
            'metadata' => ['payment_session_id' => 'sess_card'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    $request = Request::create('/', 'POST', ['id' => 'tr_card_full']);
    $event = $driver->parseWebhook($request);

    expect($event->paymentMethod)->toBe('creditcard')
        ->and($event->paymentMethodDetails['brand'])->toBe('Visa')
        ->and($event->paymentMethodDetails['last4'])->toBe('4242');
});

test('webhook returns method without details when details are not an array', function () {
    Http::fake([
        'api.mollie.com/v2/payments/tr_method_only*' => Http::response([
            'id' => 'tr_method_only',
            'status' => 'paid',
            'method' => 'banktransfer',
            'details' => null,
            'metadata' => ['payment_session_id' => 'sess_x'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    $request = Request::create('/', 'POST', ['id' => 'tr_method_only']);
    $event = $driver->parseWebhook($request);

    expect($event->paymentMethod)->toBe('banktransfer')
        ->and($event->paymentMethodDetails)->toBeNull();
});

test('test connection returns true when mollie returns methods', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake(['api.mollie.com/v2/methods' => Http::response(['count' => 1], 200)]);

    expect($driver->testConnection())->toBeTrue();
});

test('test connection returns true when mollie key is valid but restricted', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake(['api.mollie.com/v2/methods' => Http::response(['detail' => 'forbidden'], 403)]);

    expect($driver->testConnection())->toBeTrue();
});

test('test connection returns false when mollie rejects the credentials', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();
    $driver = new MollieDriver($gateway);

    Http::fake(['api.mollie.com/v2/methods' => Http::response(['detail' => 'unauthorized'], 401)]);

    expect($driver->testConnection())->toBeFalse();
});

test('test connection returns false when mollie api key is missing', function () {
    $gateway = PaymentGateway::factory()->mollie()->create(['credentials' => []]);

    expect((new MollieDriver($gateway))->testConnection())->toBeFalse();
});
