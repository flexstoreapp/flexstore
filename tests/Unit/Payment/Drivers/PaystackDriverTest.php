<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\PaymentGateway;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\PaystackDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

covers(PaystackDriver::class);

uses()->group('payment', 'paystack');

function makePaystackSession(array $overrides = []): CreateSession
{
    return new CreateSession(...[
        'internalReference' => 'ps_session_1',
        'amount' => '500.0000',
        'currencyCode' => 'NGN',
        'redirectUrls' => new RedirectUrls(
            returnUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
            failureUrl: 'https://example.com/checkout',
        ),
        'callbackUrls' => new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        'customerEmail' => 'customer@example.com',
        'description' => 'Test order',
        'metadata' => ['payment_session_id' => 'ps_session_1'],
        ...$overrides,
    ]);
}

test('implements PaymentDriver interface', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    expect($driver)->toBeInstanceOf(PaymentDriver::class);
});

test('supports refunds', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    expect($driver->supportsRefunds())->toBeTrue();
});

test('is manual returns false', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();

    expect((new PaystackDriver($gateway))->isManual())->toBeFalse();
});

test('creates hosted session with amount converted to subunits', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/nkdks46nymizns7',
                'access_code' => 'nkdks46nymizns7',
                'reference' => 'ps_session_1',
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create([
        'credentials' => [
            'public_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);
    $driver = new PaystackDriver($gateway);

    $result = $driver->createSession(makePaystackSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->redirectUrl)->toBe('https://checkout.paystack.com/nkdks46nymizns7')
        ->and($result->gatewayReference)->toBe('ps_session_1')
        ->and($result->payload['access_code'])->toBe('nkdks46nymizns7')
        ->and($result->payload)->not->toHaveKey('return_url');

    Http::assertSent(function ($request) {
        return $request['email'] === 'customer@example.com'
            && $request['amount'] === 50000
            && $request['currency'] === 'NGN'
            && $request['reference'] === 'ps_session_1'
            && $request['callback_url'] === 'https://example.com/success'
            && $request['metadata']['payment_session_id'] === 'ps_session_1'
            && $request['metadata']['cancel_action'] === 'https://example.com/cancel';
    });
});

test('creates embedded session with access code and return url', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/nkdks46nymizns7',
                'access_code' => 'nkdks46nymizns7',
                'reference' => 'ps_session_1',
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $result = $driver->createSession(makePaystackSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->redirectUrl)->toBe('https://example.com/checkout')
        ->and($result->gatewayReference)->toBe('ps_session_1')
        ->and($result->payload['access_code'])->toBe('nkdks46nymizns7')
        ->and($result->payload['return_url'])->toBe('https://example.com/success');
});

test('creates session multiplies by 100 regardless of currency decimals', function () {
    App\Models\Currency::query()->firstOrCreate(
        ['code' => 'XOF'],
        ['symbol' => 'CFA', 'exchange_rate' => 1, 'decimal_places' => 0, 'is_active' => true, 'symbol_position' => 'before', 'thousands_separator' => ',', 'decimal_separator' => '.'],
    );

    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/xof123',
                'access_code' => 'xof123',
                'reference' => 'ps_session_1',
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $driver->createSession(makePaystackSession(['amount' => '1000', 'currencyCode' => 'XOF']));

    Http::assertSent(fn ($request) => $request['amount'] === 100000 && $request['currency'] === 'XOF');
});

test('create session fails when customer email is missing', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $result = $driver->createSession(makePaystackSession(['customerEmail' => null]));

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->redirectUrl)->toBe('https://example.com/checkout')
        ->and($result->failureReason)->toBe('Customer email is required for Paystack payments.');

    Http::assertNothingSent();
});

test('create session returns failed result when paystack returns error', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => false,
            'message' => 'Duplicate Transaction Reference',
        ], 400),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $result = $driver->createSession(makePaystackSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Duplicate Transaction Reference');
});

test('create session returns failed result when secret key is missing', function () {
    $gateway = PaymentGateway::factory()->paystack()->create(['credentials' => []]);
    $driver = new PaystackDriver($gateway);

    $result = $driver->createSession(makePaystackSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Paystack secret key is not configured.');
});

test('verify payment returns paid status with card details for successful transaction', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/ps_session_1' => Http::response([
            'status' => true,
            'data' => [
                'id' => 4099260516,
                'status' => 'success',
                'reference' => 'ps_session_1',
                'amount' => 50000,
                'currency' => 'NGN',
                'channel' => 'card',
                'authorization' => [
                    'brand' => 'visa',
                    'last4' => '4081',
                ],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $verification = $driver->verifyPayment('ps_session_1', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('ps_session_1')
        ->and($verification->paymentMethod)->toBe('card')
        ->and($verification->paymentMethodDetails)->toBe(['brand' => 'visa', 'last4' => '4081']);
});

test('verify payment returns failed status for failed transaction', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/ps_session_1' => Http::response([
            'status' => true,
            'data' => ['status' => 'failed', 'reference' => 'ps_session_1'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    expect($driver->verifyPayment('ps_session_1', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Failed);
});

test('verify payment returns canceled status for abandoned transaction', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/ps_session_1' => Http::response([
            'status' => true,
            'data' => ['status' => 'abandoned', 'reference' => 'ps_session_1'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    expect($driver->verifyPayment('ps_session_1', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Canceled);
});

test('verify payment returns current status for ongoing transaction', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/ps_session_1' => Http::response([
            'status' => true,
            'data' => ['status' => 'ongoing', 'reference' => 'ps_session_1'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    expect($driver->verifyPayment('ps_session_1', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns current status when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    expect($driver->verifyPayment(null, PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns current status when paystack API fails', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['status' => false], 404),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    expect($driver->verifyPayment('unknown_ref', PaymentStatus::Paid)->status)
        ->toBe(PaymentStatus::Paid);
});

test('verify payment returns current status when secret key is missing', function () {
    $gateway = PaymentGateway::factory()->paystack()->create(['credentials' => []]);
    $driver = new PaystackDriver($gateway);

    expect($driver->verifyPayment('ps_session_1', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verify webhook returns false when secret key is empty', function () {
    $gateway = PaymentGateway::factory()->paystack()->create(['credentials' => []]);
    $driver = new PaystackDriver($gateway);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: '{}');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns false when signature header is missing', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: '{}');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns true with valid signature', function () {
    $payload = '{"event":"charge.success"}';

    $gateway = PaymentGateway::factory()->paystack()->create();
    $secretKey = $gateway->credentials['secret_key'];

    $driver = new PaystackDriver($gateway);
    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $request->headers->set('x-paystack-signature', hash_hmac('sha512', $payload, $secretKey));

    expect($driver->verifyWebhook($request))->toBeTrue();
});

test('verify webhook returns false with invalid signature', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: '{"event":"charge.success"}');
    $request->headers->set('x-paystack-signature', 'invalid_signature');

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('parse webhook handles charge.success event', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $payload = json_encode([
        'event' => 'charge.success',
        'data' => [
            'id' => 4099260516,
            'reference' => 'ps_session_42',
            'status' => 'success',
            'channel' => 'card',
            'authorization' => ['brand' => 'visa', 'last4' => '4081'],
            'metadata' => ['payment_session_id' => '42'],
        ],
    ]);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('charge.success')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('42')
        ->and($event->gatewayPaymentReference)->toBe('ps_session_42')
        ->and($event->paymentMethod)->toBe('card')
        ->and($event->paymentMethodDetails)->toBe(['brand' => 'visa', 'last4' => '4081'])
        ->and($event->shouldConfirmPaymentSession())->toBeTrue();
});

test('parse webhook handles full refund.processed event', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/ps_session_42' => Http::response([
            'status' => true,
            'data' => ['id' => 6342936041, 'status' => 'success', 'amount' => 50000, 'reference' => 'ps_session_42'],
        ]),
        'api.paystack.co/refund?transaction=6342936041' => Http::response([
            'status' => true,
            'data' => [
                ['status' => 'processed', 'amount' => 50000],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'data' => [
            'status' => 'processed',
            'transaction_reference' => 'ps_session_42',
            'refund_reference' => 'RF-123',
            'amount' => 50000,
            'currency' => 'NGN',
        ],
    ]);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('refund.processed')
        ->and($event->status)->toBe(PaymentStatus::Refunded)
        ->and($event->gatewayPaymentReference)->toBe('ps_session_42')
        ->and($event->gatewayRefundReference)->toBe('RF-123')
        ->and($event->cumulativeRefundTotal)->toBe('500.0000')
        ->and($event->payload['amount'])->toBe('500.0000')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('parse webhook handles partial refund and sums processed refunds only', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/ps_session_42' => Http::response([
            'status' => true,
            'data' => ['id' => 6342936041, 'status' => 'success', 'amount' => 50000, 'reference' => 'ps_session_42'],
        ]),
        'api.paystack.co/refund?transaction=6342936041' => Http::response([
            'status' => true,
            'data' => [
                ['status' => 'processed', 'amount' => 10000],
                ['status' => 'processed', 'amount' => 5000],
                ['status' => 'pending', 'amount' => 2000],
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'data' => [
            'status' => 'processed',
            'transaction_reference' => 'ps_session_42',
            'refund_reference' => 'RF-456',
            'amount' => 5000,
            'currency' => 'NGN',
        ],
    ]);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($event->cumulativeRefundTotal)->toBe('150.0000')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('refund webhook returns null cumulative amount when refund list API fails', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['id' => 6342936041, 'status' => 'success', 'amount' => 50000],
        ]),
        'api.paystack.co/refund*' => Http::response(['status' => false], 500),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'data' => [
            'transaction_reference' => 'ps_session_42',
            'refund_reference' => 'RF-789',
            'amount' => 5000,
        ],
    ]);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('refund webhook returns null cumulative amount when transaction cannot be resolved', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['status' => false], 404),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'data' => [
            'transaction_reference' => 'ps_unknown',
            'refund_reference' => 'RF-404',
            'amount' => 5000,
        ],
    ]);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('refund webhook returns null cumulative amount when transaction reference is missing', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $payload = json_encode([
        'event' => 'refund.processed',
        'data' => ['refund_reference' => 'RF-000', 'amount' => 5000],
    ]);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->cumulativeRefundTotal)->toBeNull()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('parse webhook handles unknown event', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $payload = json_encode([
        'event' => 'transfer.success',
        'data' => ['reference' => 'trf_123'],
    ]);

    $request = Request::create('/webhooks/payment/paystack', 'POST', content: $payload);
    $event = $driver->parseWebhook($request);

    expect($event->type)->toBe('transfer.success')
        ->and($event->status)->toBeNull()
        ->and($event->shouldConfirmPaymentSession())->toBeFalse()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('refund succeeds with pending status', function () {
    Http::fake([
        'api.paystack.co/refund' => Http::response([
            'status' => true,
            'data' => [
                'id' => 3018284,
                'status' => 'pending',
                'amount' => 25000,
            ],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'NGN',
        gatewayReference: 'ps_session_42',
        reason: 'Customer request',
    ));

    expect($refund->status)->toBe(RefundStatus::Pending)
        ->and($refund->amount)->toBe('250.0000')
        ->and($refund->gatewayReference)->toBe('3018284');

    Http::assertSent(function ($request) {
        return $request['transaction'] === 'ps_session_42'
            && $request['amount'] === 25000
            && $request['currency'] === 'NGN'
            && $request['merchant_note'] === 'Customer request';
    });
});

test('refund returns completed status for processed refund', function () {
    Http::fake([
        'api.paystack.co/refund' => Http::response([
            'status' => true,
            'data' => ['id' => 3018285, 'status' => 'processed'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'NGN',
        gatewayReference: 'ps_session_42',
    ));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->gatewayReference)->toBe('3018285');
});

test('refund fails when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'NGN',
        gatewayReference: null,
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('No gateway reference found for this order.');
});

test('refund returns failed when paystack API returns error', function () {
    Http::fake([
        'api.paystack.co/refund' => Http::response([
            'status' => false,
            'message' => 'Transaction has been fully reversed',
        ], 400),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'NGN',
        gatewayReference: 'ps_session_42',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('Transaction has been fully reversed');
});

test('refund maps unknown status to failed', function () {
    Http::fake([
        'api.paystack.co/refund' => Http::response([
            'status' => true,
            'data' => ['id' => 3018286, 'status' => 'failed'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->paystack()->create();
    $driver = new PaystackDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'NGN',
        gatewayReference: 'ps_session_42',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed);
});

test('refund returns failed when secret key is missing', function () {
    $gateway = PaymentGateway::factory()->paystack()->create(['credentials' => []]);
    $driver = new PaystackDriver($gateway);

    $refund = $driver->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'NGN',
        gatewayReference: 'ps_session_42',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('Paystack secret key is not configured.');
});

test('test connection returns true when paystack authenticates the key', function () {
    Http::fake(['api.paystack.co/transaction*' => Http::response(['status' => true, 'data' => []], 200)]);

    $gateway = PaymentGateway::factory()->paystack()->create();

    expect((new PaystackDriver($gateway))->testConnection())->toBeTrue();
});

test('test connection returns true when paystack key is valid but scoped', function () {
    Http::fake(['api.paystack.co/transaction*' => Http::response(['status' => false], 403)]);

    $gateway = PaymentGateway::factory()->paystack()->create();

    expect((new PaystackDriver($gateway))->testConnection())->toBeTrue();
});

test('test connection returns false when paystack rejects the credentials', function () {
    Http::fake(['api.paystack.co/transaction*' => Http::response(['status' => false], 401)]);

    $gateway = PaymentGateway::factory()->paystack()->create();

    expect((new PaystackDriver($gateway))->testConnection())->toBeFalse();
});

test('test connection returns false when paystack credentials are missing', function () {
    $gateway = PaymentGateway::factory()->paystack()->create(['credentials' => []]);

    expect((new PaystackDriver($gateway))->testConnection())->toBeFalse();
});
