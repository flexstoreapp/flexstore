<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\PaypalDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

covers(PaypalDriver::class);

uses()->group('payment', 'paypal');

beforeEach(function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'test_token', 'expires_in' => 28800]),
    ]);
});

function paypalCreateSession(): CreateSession
{
    return new CreateSession(
        internalReference: 'sess_1',
        amount: '50.00',
        currencyCode: 'USD',
        redirectUrls: new RedirectUrls(
            returnUrl: 'https://example.com/return',
            cancelUrl: 'https://example.com/cancel',
            failureUrl: 'https://example.com/fail',
        ),
        callbackUrls: new CallbackUrls(),
        customerEmail: 'buyer@example.com',
        storeName: 'FlexStore',
        description: 'Order #1',
        metadata: ['payment_session_id' => 'sess_1'],
    );
}

test('implements PaymentDriver interface', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    expect($driver)->toBeInstanceOf(PaymentDriver::class);
});

test('supports refunds', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    expect($driver->supportsRefunds())->toBeTrue();
});

test('verify webhook returns false when webhook id is empty', function () {
    $gateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Paypal,
        'credentials' => [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
        ],
    ]);

    $driver = new PaypalDriver($gateway);
    $request = Request::create('/webhooks/payment/paypal', 'POST');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('refund webhook extracts capture id from links', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND_123',
            'resource_type' => 'refund',
            'amount' => ['value' => '10.0000', 'currency_code' => 'USD'],
            'seller_payable_breakdown' => [
                'total_refunded_amount' => ['value' => '10.0000'],
            ],
            'links' => [
                ['rel' => 'self', 'href' => 'https://api.paypal.com/v2/payments/refunds/REFUND_123'],
                ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/CAPTURE_456'],
            ],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->gatewayPaymentReference)->toBe('CAPTURE_456')
        ->and($event->gatewayRefundReference)->toBe('REFUND_123')
        ->and($event->cumulativeRefundTotal)->toBe('10.0000')
        ->and($event->isRefundEvent())->toBeTrue()
        ->and($event->payload['refund_id'])->toBe('REFUND_123')
        ->and($event->payload['capture_id'])->toBe('CAPTURE_456');
});

test('verify payment returns canceled for voided paypal order', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_VOID' => Http::response([
            'id' => 'ORDER_VOID',
            'status' => 'VOIDED',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $verification = $driver->verifyPayment('ORDER_VOID', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Canceled)
        ->and($verification->gatewayReference)->toBe('ORDER_VOID');
});

test('verify payment returns current status for created paypal order', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_CREATED' => Http::response([
            'id' => 'ORDER_CREATED',
            'status' => 'CREATED',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $verification = $driver->verifyPayment('ORDER_CREATED', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('refund webhook falls back to resource id when links are missing', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND_789',
            'resource_type' => 'refund',
            'amount' => ['value' => '5.0000', 'currency_code' => 'USD'],
            'seller_payable_breakdown' => [
                'total_refunded_amount' => ['value' => '5.0000'],
            ],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->gatewayPaymentReference)->toBeNull()
        ->and($event->gatewayRefundReference)->toBe('REFUND_789')
        ->and($event->payload['refund_id'])->toBe('REFUND_789')
        ->and($event->payload['capture_id'])->toBeNull()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('refund webhook fetches cumulative amount from refund API and checkout session from capture API when missing', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/payments/refunds/REFUND_NO_BREAKDOWN' => Http::response([
            'id' => 'REFUND_NO_BREAKDOWN',
            'status' => 'COMPLETED',
            'seller_payable_breakdown' => [
                'total_refunded_amount' => ['value' => '15.0000', 'currency_code' => 'USD'],
            ],
        ]),
        'api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE_789' => Http::response([
            'id' => 'CAPTURE_789',
            'status' => 'PARTIALLY_REFUNDED',
            'custom_id' => 'checkout-session-42',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND_NO_BREAKDOWN',
            'resource_type' => 'refund',
            'amount' => ['value' => '15.0000', 'currency_code' => 'USD'],
            'links' => [
                ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/CAPTURE_789'],
            ],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBe('15.0000')
        ->and($event->paymentSessionId)->toBe('checkout-session-42')
        ->and($event->isRefundEvent())->toBeTrue()
        ->and($event->gatewayPaymentReference)->toBe('CAPTURE_789');
});

test('refund webhook detects partial refund status', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND_PARTIAL',
            'resource_type' => 'refund',
            'amount' => ['value' => '25.00', 'currency_code' => 'USD'],
            'seller_payable_breakdown' => [
                'total_refunded_amount' => ['value' => '25.00'],
                'gross_amount' => ['value' => '100.00'],
            ],
            'links' => [
                ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/CAPTURE_PARTIAL'],
            ],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($event->cumulativeRefundTotal)->toBe('25.00');
});

test('refund webhook detects full refund status', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND_FULL',
            'resource_type' => 'refund',
            'amount' => ['value' => '100.00', 'currency_code' => 'USD'],
            'seller_payable_breakdown' => [
                'total_refunded_amount' => ['value' => '100.00'],
                'gross_amount' => ['value' => '100.00'],
            ],
            'links' => [
                ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/CAPTURE_FULL'],
            ],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::Refunded)
        ->and($event->cumulativeRefundTotal)->toBe('100.00');
});

test('refund webhook defaults to refunded when breakdown amounts are missing', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND_NO_BREAKDOWN',
            'resource_type' => 'refund',
            'amount' => ['value' => '50.00', 'currency_code' => 'USD'],
            'links' => [
                ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/CAPTURE_NO_BREAKDOWN'],
            ],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::Refunded);
});

test('refund returns failed when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: null,
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->amount)->toBe('50.0000')
        ->and($refund->failureReason)->toBe('No gateway reference found for this order.');
});

test('refund returns completed for completed paypal refund', function () {
    Setting::setValue('base_currency', 'USD');
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/payments/captures/CAP_REFUND_OK/refund' => Http::response([
            'id' => 'REFUND_OK',
            'status' => 'COMPLETED',
            'amount' => ['value' => '25.0000', 'currency_code' => 'USD'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '25.0000',
        currencyCode: 'USD',
        gatewayReference: 'CAP_REFUND_OK',
        reason: 'Customer request',
    ));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->amount)->toBe('25.0000')
        ->and($refund->gatewayReference)->toBe('REFUND_OK');
});

test('refund returns pending for pending paypal refund', function () {
    Setting::setValue('base_currency', 'USD');
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/payments/captures/CAP_REFUND_PENDING/refund' => Http::response([
            'id' => 'REFUND_PENDING',
            'status' => 'PENDING',
            'amount' => ['value' => '30.0000', 'currency_code' => 'USD'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '30.0000',
        currencyCode: 'USD',
        gatewayReference: 'CAP_REFUND_PENDING',
    ));

    expect($refund->status)->toBe(RefundStatus::Pending);
});

test('refund fails when paypal API returns error', function () {
    Setting::setValue('base_currency', 'USD');
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/payments/captures/CAP_REFUND_FAIL/refund' => Http::response([
            'message' => 'CAPTURE_NOT_FOUND',
        ], 404),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $refund = $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: 'CAP_REFUND_FAIL',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('CAPTURE_NOT_FOUND');
});

test('verify payment extracts card payment method from completed order', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test_token']),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_CARD' => Http::response([
            'id' => 'ORDER_CARD',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'payments' => ['captures' => [['id' => 'CAP_123']]],
            ]],
            'payment_source' => [
                'card' => [
                    'brand' => 'VISA',
                    'last_digits' => '4242',
                ],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $verification = $driver->verifyPayment('ORDER_CARD', PaymentStatus::Unpaid);

    expect($verification->paymentMethod)->toBe('card')
        ->and($verification->paymentMethodDetails)->toBe(['brand' => 'VISA', 'last4' => '4242']);
});

test('verify payment extracts paypal payment source', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test_token']),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_PP' => Http::response([
            'id' => 'ORDER_PP',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'payments' => ['captures' => [['id' => 'CAP_456']]],
            ]],
            'payment_source' => [
                'paypal' => [
                    'email_address' => 'buyer@example.com',
                ],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $verification = $driver->verifyPayment('ORDER_PP', PaymentStatus::Unpaid);

    expect($verification->paymentMethod)->toBe('paypal')
        ->and($verification->paymentMethodDetails)->toBeNull();
});

test('is manual returns false', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();

    expect((new PaypalDriver($gateway))->isManual())->toBeFalse();
});

test('verifyPayment returns current status when gateway reference is null', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $verification = $driver->verifyPayment(null, PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Unpaid)
        ->and($verification->gatewayReference)->toBeNull();
});

test('verifyPayment returns current status when paypal API fails', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_FAIL' => Http::response(['error' => 'down'], 500),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    expect($driver->verifyPayment('ORDER_FAIL', PaymentStatus::Paid)->status)
        ->toBe(PaymentStatus::Paid);
});

test('verifyPayment captures approved paypal order', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_APPROVED' => Http::response([
            'id' => 'ORDER_APPROVED',
            'status' => 'APPROVED',
        ]),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_APPROVED/capture' => Http::response([
            'id' => 'ORDER_APPROVED',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'payments' => ['captures' => [['id' => 'CAP_APPROVED']]],
            ]],
            'payment_source' => ['paypal' => []],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $verification = $driver->verifyPayment('ORDER_APPROVED', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('CAP_APPROVED')
        ->and($verification->paymentMethod)->toBe('paypal');
});

test('verifyPayment keeps current status when capture call fails', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_APPROVED_FAIL' => Http::response([
            'id' => 'ORDER_APPROVED_FAIL',
            'status' => 'APPROVED',
        ]),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_APPROVED_FAIL/capture' => Http::response(['error' => 'boom'], 500),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    expect($driver->verifyPayment('ORDER_APPROVED_FAIL', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verifyPayment returns current status when access token retrieval throws', function () {
    $gateway = PaymentGateway::factory()->paypal()->create(['credentials' => []]);
    $driver = new PaypalDriver($gateway);

    expect($driver->verifyPayment('ORDER_X', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verifyPayment extracts non-card and non-paypal source as first key', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_APPLE' => Http::response([
            'id' => 'ORDER_APPLE',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'payments' => ['captures' => [['id' => 'CAP_APPLE']]],
            ]],
            'payment_source' => [
                'apple_pay' => ['token' => 'tok'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $verification = $driver->verifyPayment('ORDER_APPLE', PaymentStatus::Unpaid);

    expect($verification->paymentMethod)->toBe('apple_pay')
        ->and($verification->paymentMethodDetails)->toBeNull();
});

test('verifyPayment returns null payment method when source is missing', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_NOSRC' => Http::response([
            'id' => 'ORDER_NOSRC',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'payments' => ['captures' => [['id' => 'CAP_NOSRC']]],
            ]],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);
    $verification = $driver->verifyPayment('ORDER_NOSRC', PaymentStatus::Unpaid);

    expect($verification->paymentMethod)->toBeNull()
        ->and($verification->paymentMethodDetails)->toBeNull();
});

test('createSession in embedded mode creates paypal order', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'ORDER_NEW',
            'status' => 'CREATED',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->createSession(paypalCreateSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->gatewayReference)->toBe('ORDER_NEW')
        ->and($result->payload['paypal_order_id'])->toBe('ORDER_NEW')
        ->and($result->payload['return_url'])->toBe('https://example.com/return');
});

test('createSession in embedded mode feeds shipping and payer address to paypal', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'ORDER_NEW',
            'status' => 'CREATED',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $address = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address_line_1' => '1 Market St',
        'address_line_2' => 'Suite 5',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postal_code' => '94103',
        'country_code' => 'us',
    ];

    $session = new CreateSession(
        internalReference: 'sess_1',
        amount: '50.00',
        currencyCode: 'USD',
        redirectUrls: new RedirectUrls(
            returnUrl: 'https://example.com/return',
            cancelUrl: 'https://example.com/cancel',
            failureUrl: 'https://example.com/fail',
        ),
        callbackUrls: new CallbackUrls(),
        customerEmail: 'buyer@example.com',
        storeName: 'FlexStore',
        description: 'Order #1',
        metadata: ['payment_session_id' => 'sess_1'],
        shippingAddress: $address,
        billingAddress: $address,
    );

    $driver->createSession($session);

    Http::assertSent(function ($request) {
        if (! str_ends_with($request->url(), '/v2/checkout/orders')) {
            return false;
        }

        $shipping = $request['purchase_units'][0]['shipping'] ?? null;
        $payer = $request['payer'] ?? null;

        return $shipping['name']['full_name'] === 'Jane Doe'
            && $shipping['address']['address_line_1'] === '1 Market St'
            && $shipping['address']['admin_area_2'] === 'San Francisco'
            && $shipping['address']['admin_area_1'] === 'CA'
            && $shipping['address']['country_code'] === 'US'
            && $payer['name']['given_name'] === 'Jane'
            && $payer['email_address'] === 'buyer@example.com'
            && $payer['address']['postal_code'] === '94103';
    });
});

test('createSession in embedded mode fails when paypal returns error', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['message' => 'Bad request'], 400),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->createSession(paypalCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Bad request')
        ->and($result->redirectUrl)->toBe('https://example.com/fail');
});

test('createSession in embedded mode fails when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Embedded->value],
    ]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->createSession(paypalCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('PayPal client ID and secret are not configured.');
});

test('createSession in hosted mode returns approval url', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'ORDER_HOSTED',
            'status' => 'PAYER_ACTION_REQUIRED',
            'links' => [
                ['rel' => 'self', 'href' => 'https://api.paypal.com/v2/orders/ORDER_HOSTED'],
                ['rel' => 'payer-action', 'href' => 'https://paypal.com/approve?token=ORDER_HOSTED'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->createSession(paypalCreateSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->gatewayReference)->toBe('ORDER_HOSTED')
        ->and($result->redirectUrl)->toBe('https://paypal.com/approve?token=ORDER_HOSTED')
        ->and($result->payload)->toBe(['paypal_order_id' => 'ORDER_HOSTED']);
});

test('createSession in hosted mode feeds shipping address and provided-address preference', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'ORDER_HOSTED',
            'status' => 'PAYER_ACTION_REQUIRED',
            'links' => [
                ['rel' => 'payer-action', 'href' => 'https://paypal.com/approve?token=ORDER_HOSTED'],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $address = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address_line_1' => '1 Market St',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postal_code' => '94103',
        'country_code' => 'us',
    ];

    $driver->createSession(new CreateSession(
        internalReference: 'sess_1',
        amount: '50.00',
        currencyCode: 'USD',
        redirectUrls: new RedirectUrls(
            returnUrl: 'https://example.com/return',
            cancelUrl: 'https://example.com/cancel',
            failureUrl: 'https://example.com/fail',
        ),
        callbackUrls: new CallbackUrls(),
        customerEmail: 'buyer@example.com',
        storeName: 'FlexStore',
        description: 'Order #1',
        shippingAddress: $address,
        billingAddress: $address,
    ));

    Http::assertSent(function ($request) {
        if (! str_ends_with($request->url(), '/v2/checkout/orders')) {
            return false;
        }

        $shipping = $request['purchase_units'][0]['shipping'] ?? null;
        $paypal = $request['payment_source']['paypal'] ?? [];
        $context = $paypal['experience_context'] ?? [];

        return $shipping['address']['admin_area_2'] === 'San Francisco'
            && ($context['shipping_preference'] ?? null) === 'SET_PROVIDED_ADDRESS'
            && ($paypal['email_address'] ?? null) === 'buyer@example.com'
            && ($paypal['name']['given_name'] ?? null) === 'Jane'
            && ($paypal['address']['admin_area_1'] ?? null) === 'CA';
    });
});

test('createSession in hosted mode falls back to failure url when no approval link', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'ORDER_HOSTED_NOLINK',
            'status' => 'CREATED',
            'links' => [],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->createSession(paypalCreateSession());

    expect($result->redirectUrl)->toBe('https://example.com/fail');
});

test('createSession in hosted mode fails when paypal returns error', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['message' => 'Unprocessable'], 422),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->createSession(paypalCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Unprocessable');
});

test('createSession in hosted mode fails when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Hosted->value],
    ]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->createSession(paypalCreateSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('PayPal client ID and secret are not configured.');
});

test('uses production base url when sandbox flag is false', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.paypal.com/v2/checkout/orders/ORDER_PROD' => Http::response([
            'id' => 'ORDER_PROD',
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'payments' => ['captures' => [['id' => 'CAP_PROD']]],
            ]],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => false,
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    expect($driver->verifyPayment('ORDER_PROD', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Paid);
});

test('access token is fetched via oauth when not cached', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'oauth_token']),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_X' => Http::response([
            'id' => 'ORDER_X',
            'status' => 'CREATED',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    expect($driver->verifyPayment('ORDER_X', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);

    expect(Cache::has("paypal_access_token_{$gateway->id}"))->toBeTrue();
});

test('access token retrieval throws when paypal oauth fails', function () {
    Cache::flush();

    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['error' => 'bad'], 401),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    expect($driver->verifyPayment('ORDER_Y', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verifyWebhook returns true when paypal confirms signature', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
            'verification_status' => 'SUCCESS',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'webhook_id' => 'WH_1',
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode(['event_type' => 'PING']));
    $request->headers->set('PAYPAL-AUTH-ALGO', 'SHA256withRSA');
    $request->headers->set('PAYPAL-CERT-URL', 'https://certs.paypal.com/cert');
    $request->headers->set('PAYPAL-TRANSMISSION-ID', 'trans-1');
    $request->headers->set('PAYPAL-TRANSMISSION-SIG', 'sig');
    $request->headers->set('PAYPAL-TRANSMISSION-TIME', '2026-01-01T00:00:00Z');

    expect($driver->verifyWebhook($request))->toBeTrue();
});

test('verifyWebhook returns false when paypal rejects signature', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
            'verification_status' => 'FAILURE',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => [
            'client_id' => 'id',
            'client_secret' => 'sec',
            'sandbox' => true,
            'webhook_id' => 'WH_2',
        ],
    ]);
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([]));

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verifyWebhook returns false when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->paypal()->create([
        'credentials' => ['webhook_id' => 'WH_FAIL'],
    ]);
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([]));

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('parseWebhook handles PAYMENT.CAPTURE.COMPLETED', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => 'CAP_DONE',
            'custom_id' => 'sess_done',
            'amount' => ['value' => '100.00'],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('PAYMENT.CAPTURE.COMPLETED')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('sess_done')
        ->and($event->gatewayPaymentReference)->toBe('CAP_DONE')
        ->and($event->payload)->toBe(['capture_id' => 'CAP_DONE', 'amount' => '100.00']);
});

test('parseWebhook captures invoice_id as fallback payment session id', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => 'CAP_INV',
            'invoice_id' => 'sess_inv',
            'amount' => ['value' => '50.00'],
        ],
    ]));

    expect($driver->parseWebhook($request)->paymentSessionId)->toBe('sess_inv');
});

test('parseWebhook handles PAYMENT.CAPTURE.DENIED', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.DENIED',
        'resource' => [
            'id' => 'CAP_DENIED',
            'custom_id' => 'sess_denied',
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('PAYMENT.CAPTURE.DENIED')
        ->and($event->status)->toBe(PaymentStatus::Failed)
        ->and($event->paymentSessionId)->toBe('sess_denied')
        ->and($event->gatewayPaymentReference)->toBe('CAP_DENIED');
});

test('parseWebhook handles unknown events', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'BILLING.SUBSCRIPTION.CREATED',
        'resource' => ['id' => 'SUB_1'],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('BILLING.SUBSCRIPTION.CREATED')
        ->and($event->status)->toBeNull();
});

test('refund webhook returns null cumulative amount when API lookup fails', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/payments/refunds/REFUND_API_FAIL' => Http::response(['error' => 'down'], 500),
        'api-m.sandbox.paypal.com/v2/payments/captures/CAP_API_FAIL' => Http::response(['error' => 'down'], 500),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $request = Request::create('/webhooks/payment/paypal', 'POST', content: json_encode([
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'REFUND_API_FAIL',
            'amount' => ['value' => '10.00', 'currency_code' => 'USD'],
            'links' => [
                ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/CAP_API_FAIL'],
            ],
        ],
    ]));

    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull()
        ->and($event->paymentSessionId)->toBeNull();
});

test('refund with idempotency key sets paypal request id header', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/payments/captures/CAP_IDEM/refund' => Http::response([
            'id' => 'REFUND_IDEM',
            'status' => 'COMPLETED',
            'amount' => ['value' => '5.00', 'currency_code' => 'USD'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '5.00',
        currencyCode: 'USD',
        gatewayReference: 'CAP_IDEM',
        idempotencyKey: 'idem-1',
    ));

    expect($result->status)->toBe(RefundStatus::Completed);

    Http::assertSent(fn ($request) => $request->hasHeader('PayPal-Request-Id', 'idem-1'));
});

test('refund maps unknown status to failed', function () {
    Cache::put('paypal_access_token_1', 'test_token');

    Http::fake([
        'api-m.sandbox.paypal.com/v2/payments/captures/CAP_UNKNOWN/refund' => Http::response([
            'id' => 'REFUND_UNKNOWN',
            'status' => 'SOMETHING_ELSE',
            'amount' => ['value' => '10.00', 'currency_code' => 'USD'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'USD',
        gatewayReference: 'CAP_UNKNOWN',
    ));

    expect($result->status)->toBe(RefundStatus::Failed);
});

test('refund returns failed when exception is thrown', function () {
    $gateway = PaymentGateway::factory()->paypal()->create(['credentials' => []]);
    $driver = new PaypalDriver($gateway);

    $result = $driver->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'USD',
        gatewayReference: 'CAP_BOOM',
    ));

    expect($result->status)->toBe(RefundStatus::Failed)
        ->and($result->failureReason)->toBe('PayPal client ID and secret are not configured.');
});

test('test connection returns true when paypal issues an access token', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    Http::fake(['api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'A21AA'], 200)]);

    expect($driver->testConnection())->toBeTrue();
});

test('test connection returns false when paypal rejects the credentials', function () {
    $gateway = PaymentGateway::factory()->paypal()->create();
    $driver = new PaypalDriver($gateway);

    Http::swap(new Illuminate\Http\Client\Factory());
    Http::fake(['api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401)]);

    expect($driver->testConnection())->toBeFalse();
});

test('test connection returns false when paypal credentials are missing', function () {
    $gateway = PaymentGateway::factory()->paypal()->create(['credentials' => []]);

    expect((new PaypalDriver($gateway))->testConnection())->toBeFalse();
});
