<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\RazorpayDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

covers(RazorpayDriver::class);

uses()->group('payment', 'razorpay');

test('implements PaymentDriver interface', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    expect($driver)->toBeInstanceOf(PaymentDriver::class);
});

test('supports refunds', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    expect($driver->supportsRefunds())->toBeTrue();
});

test('verify webhook returns false when webhook secret is empty', function () {
    $gateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Razorpay,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
        ],
    ]);

    $driver = new RazorpayDriver($gateway);
    $request = Request::create('/webhooks/payment/razorpay', 'POST');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns false when signature header is missing', function () {
    $gateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Razorpay,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'webhook_secret' => 'whsec_test_123',
        ],
    ]);

    $driver = new RazorpayDriver($gateway);
    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: '{}');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns true with valid signature', function () {
    $webhookSecret = 'whsec_test_123';
    $payload = '{"event":"payment.captured"}';
    $signature = hash_hmac('sha256', $payload, $webhookSecret);

    $gateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Razorpay,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'webhook_secret' => $webhookSecret,
        ],
    ]);

    $driver = new RazorpayDriver($gateway);
    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $request->headers->set('X-Razorpay-Signature', $signature);

    expect($driver->verifyWebhook($request))->toBeTrue();
});

test('verify webhook returns false with invalid signature', function () {
    $gateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Razorpay,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'webhook_secret' => 'whsec_test_123',
        ],
    ]);

    $driver = new RazorpayDriver($gateway);
    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: '{"event":"payment.captured"}');
    $request->headers->set('X-Razorpay-Signature', 'invalid_signature');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('parse webhook handles payment.authorized event', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'payment.authorized',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_test_123',
                    'order_id' => 'order_test_456',
                    'notes' => ['payment_session_id' => '42'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.authorized')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('42')
        ->and($event->gatewayPaymentReference)->toBe('pay_test_123')
        ->and($event->gatewayOrderReference)->toBe('order_test_456');
});

test('parse webhook handles payment.captured event', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_test_123',
                    'order_id' => 'order_test_456',
                    'notes' => ['payment_session_id' => '42'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.captured')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('42')
        ->and($event->gatewayPaymentReference)->toBe('pay_test_123');
});

test('parse webhook handles payment.failed event', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'payment.failed',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_test_123',
                    'order_id' => 'order_test_456',
                    'notes' => ['payment_session_id' => '42'],
                    'error_description' => 'Payment declined',
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('payment.failed')
        ->and($event->status)->toBe(PaymentStatus::Failed)
        ->and($event->paymentSessionId)->toBe('42')
        ->and($event->gatewayPaymentReference)->toBe('pay_test_123');
});

test('parse webhook handles refund.processed event', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => [
                'entity' => [
                    'id' => 'rfnd_test_123',
                    'payment_id' => 'pay_test_456',
                    'amount' => 50000,
                    'notes' => ['payment_session_id' => '42'],
                ],
            ],
            'payment' => [
                'entity' => [
                    'id' => 'pay_test_456',
                    'amount' => 50000,
                    'amount_refunded' => 50000,
                    'refund_status' => 'full',
                    'notes' => ['payment_session_id' => '42'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('refund.processed')
        ->and($event->status)->toBe(PaymentStatus::Refunded)
        ->and($event->paymentSessionId)->toBe('42')
        ->and($event->gatewayPaymentReference)->toBe('pay_test_456')
        ->and($event->gatewayRefundReference)->toBe('rfnd_test_123')
        ->and($event->cumulativeRefundTotal)->toBe('500.0000')
        ->and($event->isRefundEvent())->toBeTrue()
        ->and($event->payload['refund_id'])->toBe('rfnd_test_123')
        ->and($event->gatewayOrderReference)->toBeNull()
        ->and($event->payload['amount'])->toBe('500.0000');
});

test('parse refund webhook includes razorpay order id from payment entity', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => [
                'entity' => [
                    'id' => 'rfnd_test_123',
                    'payment_id' => 'pay_test_456',
                    'amount' => 50000,
                    'notes' => [],
                ],
            ],
            'payment' => [
                'entity' => [
                    'id' => 'pay_test_456',
                    'order_id' => 'order_test_789',
                    'amount' => 100000,
                    'amount_refunded' => 50000,
                    'refund_status' => 'partial',
                    'notes' => ['payment_session_id' => '42'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->gatewayOrderReference)->toBe('order_test_789')
        ->and($event->payload['razorpay_payment_id'])->toBe('pay_test_456');
});

test('parse webhook resolves checkout session id from payment notes when refund notes lack it', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => [
                'entity' => [
                    'id' => 'rfnd_test_123',
                    'payment_id' => 'pay_test_456',
                    'amount' => 50000,
                    'notes' => ['reason' => 'Customer request'],
                ],
            ],
            'payment' => [
                'entity' => [
                    'id' => 'pay_test_456',
                    'amount' => 100000,
                    'amount_refunded' => 50000,
                    'refund_status' => 'partial',
                    'notes' => ['payment_session_id' => '99'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->paymentSessionId)->toBe('99')
        ->and($event->gatewayPaymentReference)->toBe('pay_test_456')
        ->and($event->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($event->cumulativeRefundTotal)->toBe('500.0000')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('parse refund webhook fetches cumulative amount from API when payment entity is missing', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/pay_test_456' => Http::response([
            'id' => 'pay_test_456',
            'amount' => 100000,
            'amount_refunded' => 50000,
            'status' => 'refunded',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => [
                'entity' => [
                    'id' => 'rfnd_test_123',
                    'payment_id' => 'pay_test_456',
                    'amount' => 50000,
                    'notes' => ['payment_session_id' => '42'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('refund.processed')
        ->and($event->cumulativeRefundTotal)->toBe('500.0000')
        ->and($event->isRefundEvent())->toBeTrue()
        ->and($event->gatewayRefundReference)->toBe('rfnd_test_123');

    Http::assertSentCount(1);
});

test('refund returns pending status for pending razorpay refund', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/pay_test_456/refund' => Http::response([
            'id' => 'rfnd_test_789',
            'amount' => 50000,
            'status' => 'pending',
            'payment_id' => 'pay_test_456',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '500.0000',
        currencyCode: 'USD',
        gatewayReference: 'pay_test_456',
    ));

    expect($refund->status)->toBe(RefundStatus::Pending);
});

test('converts zero-decimal currency amount in refund webhook', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    App\Models\Currency::query()->firstOrCreate(
        ['code' => 'JPY'],
        ['symbol' => '¥', 'exchange_rate' => 1, 'decimal_places' => 0, 'is_active' => true, 'symbol_position' => 'before', 'thousands_separator' => ',', 'decimal_separator' => '.'],
    );

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => [
                'entity' => [
                    'id' => 'rfnd_jpy_123',
                    'payment_id' => 'pay_jpy_456',
                    'amount' => 1000,
                    'notes' => ['payment_session_id' => 'jpy_session'],
                ],
            ],
            'payment' => [
                'entity' => [
                    'id' => 'pay_jpy_456',
                    'amount' => 1000,
                    'amount_refunded' => 1000,
                    'currency' => 'JPY',
                    'refund_status' => 'full',
                    'notes' => ['payment_session_id' => 'jpy_session'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBe('1000.0000')
        ->and($event->payload['amount'])->toBe('1000.0000');
});

test('parse webhook handles unknown event', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'some.unknown.event',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_test_123',
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('some.unknown.event')
        ->and($event->status)->toBeNull()
        ->and($event->shouldConfirmPaymentSession())->toBeFalse();
});

test('creates hosted session', function () {
    Setting::setValue('base_currency', 'INR');

    Http::fake([
        'api.razorpay.com/v1/payment_links' => Http::response([
            'id' => 'plink_test_123',
            'short_url' => 'https://rzp.io/i/test123',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create([
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '1000.0000']);

    $driver = new RazorpayDriver($gateway);
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

    expect($session->status)->toBe(PaymentStatus::Unpaid)
        ->and($session->redirectUrl)->toBe('https://rzp.io/i/test123')
        ->and($session->gatewayReference)->toBe('plink_test_123')
        ->and($session->payload['payment_link_id'])->toBe('plink_test_123');

    Http::assertSentCount(1);
});

test('creates embedded session', function () {
    Setting::setValue('base_currency', 'INR');

    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test_123',
            'amount' => 100000,
            'currency' => 'INR',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create([
        'credentials' => [
            'key_id' => 'rzp_test_abc',
            'key_secret' => 'test_secret',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $checkoutSession = CheckoutSession::factory()->create(['total' => '1000.0000']);

    $driver = new RazorpayDriver($gateway);
    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success', cancelUrl: 'https://example.com/cancel', failureUrl: route('checkout.create')),
        callbackUrls: new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        metadata: ['payment_session_id' => $checkoutSession->id],
    ));

    expect($session->status)->toBe(PaymentStatus::Unpaid)
        ->and($session->redirectUrl)->toBe(route('checkout.create'))
        ->and($session->gatewayReference)->toBe('order_test_123')
        ->and($session->payload['razorpay_order_id'])->toBe('order_test_123')
        ->and($session->payload['key_id'])->toBe('rzp_test_abc')
        ->and($session->payload)->toHaveKey('return_url');

    Http::assertSent(function ($request) {
        return $request['payment_capture'] === 1;
    });
});

test('verify payment returns paid status when order is paid', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_test_123' => Http::response([
            'id' => 'order_test_123',
            'status' => 'paid',
        ]),
        'api.razorpay.com/v1/orders/order_test_123/payments' => Http::response([
            'items' => [
                ['id' => 'pay_test_456', 'status' => 'captured'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('order_test_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('pay_test_456');
});

test('verify payment returns paid status when order is attempted with captured payment', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_test_123' => Http::response([
            'id' => 'order_test_123',
            'status' => 'attempted',
        ]),
        'api.razorpay.com/v1/orders/order_test_123/payments' => Http::response([
            'items' => [
                ['id' => 'pay_test_failed', 'status' => 'failed'],
                ['id' => 'pay_test_456', 'status' => 'captured'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('order_test_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('pay_test_456');
});

test('verify payment returns failed when order is attempted with only failed payments', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_test_123' => Http::response([
            'id' => 'order_test_123',
            'status' => 'attempted',
        ]),
        'api.razorpay.com/v1/orders/order_test_123/payments' => Http::response([
            'items' => [
                ['id' => 'pay_test_failed', 'status' => 'failed'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('order_test_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Failed)
        ->and($verification->gatewayReference)->toBe('pay_test_failed');
});

test('verify payment returns current status when order is not paid', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_test_123' => Http::response([
            'id' => 'order_test_123',
            'status' => 'created',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('order_test_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns paid status for payment link', function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links/plink_test_123' => Http::response([
            'id' => 'plink_test_123',
            'status' => 'paid',
            'payments' => [
                ['payment_id' => 'pay_test_456', 'amount' => 100000, 'status' => 'captured'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('plink_test_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('pay_test_456');
});

test('verify payment returns current status for unpaid payment link', function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links/plink_test_123' => Http::response([
            'id' => 'plink_test_123',
            'status' => 'created',
            'payments' => null,
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('plink_test_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns current status when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment(null, PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns failed when order has failed payment', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_fail_123' => Http::response([
            'id' => 'order_fail_123',
            'status' => 'attempted',
        ]),
        'api.razorpay.com/v1/orders/order_fail_123/payments' => Http::response([
            'items' => [
                ['id' => 'pay_fail_456', 'status' => 'failed'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('order_fail_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Failed)
        ->and($verification->gatewayReference)->toBe('pay_fail_456');
});

test('verify payment link returns canceled for canceled link', function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links/plink_cancel_123' => Http::response([
            'id' => 'plink_cancel_123',
            'status' => 'cancelled',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('plink_cancel_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Canceled)
        ->and($verification->gatewayReference)->toBe('plink_cancel_123');
});

test('verify payment link returns failed for expired link', function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links/plink_expired_123' => Http::response([
            'id' => 'plink_expired_123',
            'status' => 'expired',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();

    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('plink_expired_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Failed)
        ->and($verification->gatewayReference)->toBe('plink_expired_123');
});

test('refund succeeds with order reference', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_test_123/payments' => Http::response([
            'items' => [
                ['id' => 'pay_test_456', 'status' => 'captured'],
            ],
        ]),
        'api.razorpay.com/v1/payments/pay_test_456/refund' => Http::response([
            'id' => 'rfnd_test_789',
            'amount' => 50000,
            'status' => 'processed',
            'payment_id' => 'pay_test_456',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '500.0000',
        currencyCode: 'USD',
        gatewayReference: 'order_test_123',
        reason: 'Customer request',
    ));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->amount)->toBe('500.0000')
        ->and($refund->gatewayReference)->toBe('rfnd_test_789');
});

test('refund succeeds with payment id reference', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/pay_test_456/refund' => Http::response([
            'id' => 'rfnd_test_789',
            'amount' => 50000,
            'status' => 'processed',
            'payment_id' => 'pay_test_456',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '500.0000',
        currencyCode: 'USD',
        gatewayReference: 'pay_test_456',
    ));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->amount)->toBe('500.0000')
        ->and($refund->gatewayReference)->toBe('rfnd_test_789');
});

test('refund fails when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '500.0000',
        currencyCode: 'USD',
        gatewayReference: null,
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('No gateway reference found for this order.');
});

test('verify payment extracts card payment method details from order payments', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_card_test' => Http::response([
            'id' => 'order_card_test',
            'status' => 'paid',
        ]),
        'api.razorpay.com/v1/orders/order_card_test/payments' => Http::response([
            'items' => [
                [
                    'id' => 'pay_card_123',
                    'status' => 'captured',
                    'method' => 'card',
                    'card' => [
                        'network' => 'Visa',
                        'last4' => '4242',
                    ],
                ],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);
    $verification = $driver->verifyPayment('order_card_test', PaymentStatus::Unpaid);

    expect($verification->paymentMethod)->toBe('card')
        ->and($verification->paymentMethodDetails)->toBe(['brand' => 'Visa', 'last4' => '4242']);
});

test('is manual returns false', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();

    expect((new RazorpayDriver($gateway))->isManual())->toBeFalse();
});

test('verifyPayment returns current status when razorpay API throws exception', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create(['credentials' => []]);
    $driver = new RazorpayDriver($gateway);

    expect($driver->verifyPayment('order_abc', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verifyPayment returns current status when razorpay API fails', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_down*' => Http::response(['error' => 'down'], 500),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    expect($driver->verifyPayment('order_down', PaymentStatus::Paid)->status)
        ->toBe(PaymentStatus::Paid);
});

test('verifyPayment link returns current status when payment link API fails', function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links/plink_down*' => Http::response(['error' => 'down'], 500),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    expect($driver->verifyPayment('plink_down', PaymentStatus::Paid)->status)
        ->toBe(PaymentStatus::Paid);
});

test('refund fails when resolvePaymentId returns null for non-pay reference', function () {
    Http::fake([
        'api.razorpay.com/v1/orders/order_nopay/payments' => Http::response(['items' => []], 200),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'INR',
        gatewayReference: 'order_nopay',
    ));

    expect($result->status)->toBe(RefundStatus::Failed)
        ->and($result->failureReason)->toBe('No payment found for this order.');
});

test('refund resolves payment id from payment link', function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links/plink_refund*' => Http::response([
            'payments' => [['payment_id' => 'pay_link_123']],
        ], 200),
        'api.razorpay.com/v1/payments/pay_link_123/refund' => Http::response([
            'id' => 'rfnd_link_1',
            'status' => 'processed',
        ], 200),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '5.00',
        currencyCode: 'INR',
        gatewayReference: 'plink_refund',
        orderId: 42,
        reason: 'Customer request',
        idempotencyKey: 'refund-key-1',
    ));

    expect($result->status)->toBe(RefundStatus::Completed)
        ->and($result->gatewayReference)->toBe('rfnd_link_1');
});

test('refund returns failed when razorpay API returns error', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/pay_fail/refund' => Http::response([
            'error' => ['description' => 'Refund not allowed'],
        ], 400),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'INR',
        gatewayReference: 'pay_fail',
    ));

    expect($result->status)->toBe(RefundStatus::Failed)
        ->and($result->failureReason)->toBe('Refund not allowed');
});

test('refund maps unknown status to failed', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/pay_unknown/refund' => Http::response([
            'id' => 'rfnd_unknown',
            'status' => 'queued',
        ], 200),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'INR',
        gatewayReference: 'pay_unknown',
    ));

    expect($result->status)->toBe(RefundStatus::Failed);
});

test('refund returns failed when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create(['credentials' => []]);
    $driver = new RazorpayDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'INR',
        gatewayReference: 'pay_noclient',
    ));

    expect($result->status)->toBe(RefundStatus::Failed)
        ->and($result->failureReason)->toBe('Razorpay key ID and key secret are not configured.');
});

test('createEmbeddedSession returns failed result when razorpay returns error', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'error' => ['description' => 'Invalid amount'],
        ], 400),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create([
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $session = new CreateSession(
        internalReference: 'sess_1',
        amount: '50.00',
        currencyCode: 'INR',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/r', failureUrl: 'https://example.com/f'),
        callbackUrls: new CallbackUrls(),
    );

    $result = (new RazorpayDriver($gateway))->createSession($session);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Invalid amount');
});

test('createEmbeddedSession returns failed result when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Embedded->value],
    ]);

    $session = new CreateSession(
        internalReference: 'sess_2',
        amount: '50.00',
        currencyCode: 'INR',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/r', failureUrl: 'https://example.com/f'),
        callbackUrls: new CallbackUrls(),
    );

    $result = (new RazorpayDriver($gateway))->createSession($session);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Razorpay key ID and key secret are not configured.');
});

test('createHostedSession returns failed result when razorpay returns error', function () {
    Http::fake([
        'api.razorpay.com/v1/payment_links' => Http::response([
            'error' => ['description' => 'Invalid currency'],
        ], 400),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create([
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $session = new CreateSession(
        internalReference: 'sess_3',
        amount: '50.00',
        currencyCode: 'INR',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/r', failureUrl: 'https://example.com/f'),
        callbackUrls: new CallbackUrls(),
    );

    $result = (new RazorpayDriver($gateway))->createSession($session);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Invalid currency');
});

test('createHostedSession returns failed result when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Hosted->value],
    ]);

    $session = new CreateSession(
        internalReference: 'sess_4',
        amount: '50.00',
        currencyCode: 'INR',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/r', failureUrl: 'https://example.com/f'),
        callbackUrls: new CallbackUrls(),
    );

    $result = (new RazorpayDriver($gateway))->createSession($session);

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Razorpay key ID and key secret are not configured.');
});

test('refund webhook returns null cumulative amount when payment API fails', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/pay_miss' => Http::response(['error' => 'down'], 500),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => ['entity' => ['id' => 'rfnd_nopay', 'payment_id' => 'pay_miss', 'amount' => 500]],
            'payment' => ['entity' => ['id' => 'pay_miss', 'currency' => 'INR']],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull();
});

test('refund webhook returns null cumulative amount when payment id is missing', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => ['entity' => ['id' => 'rfnd_id', 'amount' => 100]],
            'payment' => ['entity' => ['currency' => 'INR']],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull();
});

test('refund webhook returns null cumulative amount when payment has zero amount refunded', function () {
    Http::fake([
        'api.razorpay.com/v1/payments/pay_zero' => Http::response([
            'id' => 'pay_zero',
            'amount_refunded' => 0,
            'currency' => 'INR',
        ], 200),
    ]);

    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => ['entity' => ['id' => 'rfnd_zero', 'payment_id' => 'pay_zero', 'amount' => 0]],
            'payment' => ['entity' => ['id' => 'pay_zero', 'currency' => 'INR']],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull();
});

test('refund webhook returns null cumulative amount when API call throws', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create(['credentials' => []]);
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'payload' => [
            'refund' => ['entity' => ['id' => 'rfnd', 'payment_id' => 'pay', 'amount' => 100]],
            'payment' => ['entity' => ['id' => 'pay']],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull();
});

test('webhook extracts payment method details from captured event', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    $payload = json_encode([
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_upi_123',
                    'order_id' => 'order_upi_456',
                    'method' => 'upi',
                    'notes' => ['payment_session_id' => '99'],
                ],
            ],
        ],
    ]);

    $request = Request::create('/webhooks/payment/razorpay', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->paymentMethod)->toBe('upi')
        ->and($event->paymentMethodDetails)->toBeNull();
});

test('test connection returns true when razorpay lists payments', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    Http::fake(['api.razorpay.com/v1/payments*' => Http::response(['count' => 0, 'items' => []], 200)]);

    expect($driver->testConnection())->toBeTrue();
});

test('test connection returns true when razorpay key is valid but restricted', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    Http::fake(['api.razorpay.com/v1/payments*' => Http::response(['error' => []], 403)]);

    expect($driver->testConnection())->toBeTrue();
});

test('test connection returns false when razorpay rejects the credentials', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();
    $driver = new RazorpayDriver($gateway);

    Http::fake(['api.razorpay.com/v1/payments*' => Http::response(['error' => []], 401)]);

    expect($driver->testConnection())->toBeFalse();
});

test('test connection returns false when razorpay credentials are missing', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create(['credentials' => []]);

    expect((new RazorpayDriver($gateway))->testConnection())->toBeFalse();
});
