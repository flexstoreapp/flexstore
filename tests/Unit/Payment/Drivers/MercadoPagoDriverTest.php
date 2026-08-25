<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\PaymentGateway;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\MercadoPagoDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

covers(MercadoPagoDriver::class);

uses()->group('payment', 'mercadopago');

function makeMercadoPagoSession(array $overrides = []): CreateSession
{
    return new CreateSession(...[
        'internalReference' => 'mp_session_1',
        'amount' => '500.0000',
        'currencyCode' => 'BRL',
        'redirectUrls' => new RedirectUrls(
            returnUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
            failureUrl: 'https://example.com/checkout',
        ),
        'callbackUrls' => new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        'customerEmail' => 'customer@example.com',
        'storeName' => 'Flexstore',
        'description' => 'Test order',
        'metadata' => ['payment_session_id' => 'mp_session_1'],
        'billingAddress' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'postal_code' => '12345', 'address_line_1' => 'Main St'],
        ...$overrides,
    ]);
}

test('implements PaymentDriver interface', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect(new MercadoPagoDriver($gateway))->toBeInstanceOf(PaymentDriver::class);
});

test('supports refunds', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->supportsRefunds())->toBeTrue();
});

test('is manual returns false', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->isManual())->toBeFalse();
});

test('creates hosted session redirecting to init point', function () {
    Http::fake([
        'api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'pref_123',
            'init_point' => 'https://www.mercadopago.com/checkout/v1/redirect?pref_id=pref_123',
            'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/v1/redirect?pref_id=pref_123',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create([
        'credentials' => [
            'public_key' => 'TEST-pk',
            'access_token' => 'TEST-token',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $result = (new MercadoPagoDriver($gateway))->createSession(makeMercadoPagoSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->redirectUrl)->toBe('https://www.mercadopago.com/checkout/v1/redirect?pref_id=pref_123')
        ->and($result->gatewayReference)->toBe('pref_123')
        ->and($result->payload['mercadopago_preference_id'])->toBe('pref_123')
        ->and($result->payload)->not->toHaveKey('return_url');

    Http::assertSent(function ($request) {
        return $request['items'][0]['currency_id'] === 'BRL'
            && (float) $request['items'][0]['unit_price'] === 500.0
            && $request['external_reference'] === 'mp_session_1'
            && $request['auto_return'] === 'approved'
            && $request['notification_url'] === 'https://example.com/webhook'
            && $request['back_urls']['success'] === 'https://example.com/success'
            && $request['metadata']['payment_session_id'] === 'mp_session_1'
            && $request['payer']['email'] === 'customer@example.com';
    });
});

test('creates embedded session with preference id and return url', function () {
    Http::fake([
        'api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'pref_123',
            'init_point' => 'https://www.mercadopago.com/checkout/v1/redirect?pref_id=pref_123',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $result = (new MercadoPagoDriver($gateway))->createSession(makeMercadoPagoSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->redirectUrl)->toBe('https://example.com/checkout')
        ->and($result->gatewayReference)->toBe('pref_123')
        ->and($result->payload['preference_id'])->toBe('pref_123')
        ->and($result->payload['return_url'])->toBe('https://example.com/success');
});

test('create session returns failed result when mercado pago returns error', function () {
    Http::fake([
        'api.mercadopago.com/checkout/preferences' => Http::response([
            'message' => 'invalid_currency_id',
        ], 400),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $result = (new MercadoPagoDriver($gateway))->createSession(makeMercadoPagoSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('invalid_currency_id');
});

test('create session returns failed result when access token is missing', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create(['credentials' => []]);

    $result = (new MercadoPagoDriver($gateway))->createSession(makeMercadoPagoSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toBe('Mercado Pago access token is not configured.');
});

test('verify payment resolves the payment from the preference and returns paid', function () {
    Http::fake([
        'api.mercadopago.com/merchant_orders/search*' => Http::response([
            'elements' => [
                ['payments' => [['id' => 987654, 'status' => 'approved']]],
            ],
        ]),
        'api.mercadopago.com/v1/payments/987654' => Http::response([
            'id' => 987654,
            'status' => 'approved',
            'external_reference' => 'mp_session_1',
            'payment_type_id' => 'credit_card',
            'payment_method_id' => 'visa',
            'card' => ['last_four_digits' => '4321'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $verification = (new MercadoPagoDriver($gateway))->verifyPayment('pref_123', PaymentStatus::Unpaid);

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('987654')
        ->and($verification->paymentMethod)->toBe('credit_card')
        ->and($verification->paymentMethodDetails)->toBe(['brand' => 'visa', 'last4' => '4321']);
});

test('verify payment returns current status when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->verifyPayment(null, PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns current status when no payment is found', function () {
    Http::fake([
        'api.mercadopago.com/merchant_orders/search*' => Http::response(['elements' => []]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->verifyPayment('pref_123', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('verify payment returns current status when the payment API fails', function () {
    Http::fake([
        'api.mercadopago.com/merchant_orders/search*' => Http::response([
            'elements' => [['payments' => [['id' => 987654, 'status' => 'approved']]]],
        ]),
        'api.mercadopago.com/v1/payments/*' => Http::response([], 500),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->verifyPayment('pref_123', PaymentStatus::Paid)->status)
        ->toBe(PaymentStatus::Paid);
});

test('refund succeeds with completed status for approved refund', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments/987654/refunds' => Http::response([
            'id' => 111222,
            'status' => 'approved',
            'amount' => 250.0,
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $refund = (new MercadoPagoDriver($gateway))->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'BRL',
        gatewayReference: '987654',
        reason: 'Customer request',
    ));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->amount)->toBe('250.0000')
        ->and($refund->gatewayReference)->toBe('111222');

    Http::assertSent(fn ($request) => (float) $request['amount'] === 250.0);
});

test('refund maps in process status to pending', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments/987654/refunds' => Http::response([
            'id' => 111333,
            'status' => 'in_process',
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $refund = (new MercadoPagoDriver($gateway))->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'BRL',
        gatewayReference: '987654',
    ));

    expect($refund->status)->toBe(RefundStatus::Pending);
});

test('refund fails when no gateway reference', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $refund = (new MercadoPagoDriver($gateway))->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'BRL',
        gatewayReference: null,
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('No gateway reference found for this order.');
});

test('refund returns failed when mercado pago returns error', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments/987654/refunds' => Http::response([
            'message' => 'refund not allowed',
        ], 400),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $refund = (new MercadoPagoDriver($gateway))->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'BRL',
        gatewayReference: '987654',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('refund not allowed');
});

test('refund returns failed when access token is missing', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create(['credentials' => []]);

    $refund = (new MercadoPagoDriver($gateway))->refund(new RefundPayment(
        amount: '250.0000',
        currencyCode: 'BRL',
        gatewayReference: '987654',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('Mercado Pago access token is not configured.');
});

test('verify webhook returns false when webhook secret is empty', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create([
        'credentials' => ['access_token' => 'TEST-token'],
    ]);

    $request = Request::create('/webhooks/payment/mercadopago?data.id=123', 'POST', content: '{}');

    expect((new MercadoPagoDriver($gateway))->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns false when signature header is missing', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $request = Request::create('/webhooks/payment/mercadopago?data.id=123', 'POST', content: '{}');

    expect((new MercadoPagoDriver($gateway))->verifyWebhook($request))->toBeFalse();
});

test('verify webhook returns true with a valid signature', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();
    $secret = $gateway->credentials['webhook_secret'];

    $ts = '1700000000';
    $requestId = 'req-abc';
    $manifest = "id:123456;request-id:{$requestId};ts:{$ts};";
    $signature = hash_hmac('sha256', $manifest, $secret);

    $request = Request::create('/webhooks/payment/mercadopago?data.id=123456&type=payment', 'POST', content: '{}');
    $request->headers->set('x-signature', "ts={$ts},v1={$signature}");
    $request->headers->set('x-request-id', $requestId);

    expect((new MercadoPagoDriver($gateway))->verifyWebhook($request))->toBeTrue();
});

test('verify webhook returns false with an invalid signature', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $request = Request::create('/webhooks/payment/mercadopago?data.id=123456&type=payment', 'POST', content: '{}');
    $request->headers->set('x-signature', 'ts=1700000000,v1=deadbeef');
    $request->headers->set('x-request-id', 'req-abc');

    expect((new MercadoPagoDriver($gateway))->verifyWebhook($request))->toBeFalse();
});

test('parse webhook handles an approved payment notification', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments/987654' => Http::response([
            'id' => 987654,
            'status' => 'approved',
            'external_reference' => '42',
            'transaction_amount' => 500,
            'transaction_amount_refunded' => 0,
            'payment_type_id' => 'credit_card',
            'payment_method_id' => 'master',
            'card' => ['last_four_digits' => '1234'],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $request = Request::create('/webhooks/payment/mercadopago', 'POST', content: json_encode([
        'type' => 'payment',
        'action' => 'payment.updated',
        'data' => ['id' => '987654'],
    ]));

    $event = (new MercadoPagoDriver($gateway))->parseWebhook($request);

    expect($event->type)->toBe('payment.approved')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('42')
        ->and($event->gatewayPaymentReference)->toBe('987654')
        ->and($event->paymentMethod)->toBe('credit_card')
        ->and($event->paymentMethodDetails)->toBe(['brand' => 'master', 'last4' => '1234'])
        ->and($event->shouldConfirmPaymentSession())->toBeTrue();
});

test('parse webhook handles a full refund notification', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments/987654' => Http::response([
            'id' => 987654,
            'status' => 'refunded',
            'external_reference' => '42',
            'currency_id' => 'BRL',
            'transaction_amount' => 500,
            'transaction_amount_refunded' => 500,
            'refunds' => [['id' => 111222, 'amount' => 500]],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $request = Request::create('/webhooks/payment/mercadopago', 'POST', content: json_encode([
        'type' => 'payment',
        'data' => ['id' => '987654'],
    ]));

    $event = (new MercadoPagoDriver($gateway))->parseWebhook($request);

    expect($event->type)->toBe('payment.refunded')
        ->and($event->status)->toBe(PaymentStatus::Refunded)
        ->and($event->gatewayPaymentReference)->toBe('987654')
        ->and($event->gatewayRefundReference)->toBe('111222')
        ->and($event->cumulativeRefundTotal)->toBe('500.00')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('parse webhook handles a partial refund notification', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments/987654' => Http::response([
            'id' => 987654,
            'status' => 'approved',
            'external_reference' => '42',
            'currency_id' => 'BRL',
            'transaction_amount' => 500,
            'transaction_amount_refunded' => 150,
            'refunds' => [['id' => 111333, 'amount' => 150]],
        ]),
    ]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $request = Request::create('/webhooks/payment/mercadopago', 'POST', content: json_encode([
        'type' => 'payment',
        'data' => ['id' => '987654'],
    ]));

    $event = (new MercadoPagoDriver($gateway))->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($event->cumulativeRefundTotal)->toBe('150.00')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('parse webhook ignores non-payment notifications', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    $request = Request::create('/webhooks/payment/mercadopago', 'POST', content: json_encode([
        'type' => 'merchant_order',
        'data' => ['id' => '555'],
    ]));

    $event = (new MercadoPagoDriver($gateway))->parseWebhook($request);

    expect($event->type)->toBe('merchant_order')
        ->and($event->status)->toBeNull()
        ->and($event->shouldConfirmPaymentSession())->toBeFalse()
        ->and($event->isRefundEvent())->toBeFalse();
});

test('test connection returns true when mercado pago lists payment methods', function () {
    Http::fake(['api.mercadopago.com/v1/payment_methods' => Http::response([], 200)]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->testConnection())->toBeTrue();
});

test('test connection returns true when mercado pago token is valid but scoped', function () {
    Http::fake(['api.mercadopago.com/v1/payment_methods' => Http::response([], 403)]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->testConnection())->toBeTrue();
});

test('test connection returns false when mercado pago rejects the credentials', function () {
    Http::fake(['api.mercadopago.com/v1/payment_methods' => Http::response([], 401)]);

    $gateway = PaymentGateway::factory()->mercadoPago()->create();

    expect((new MercadoPagoDriver($gateway))->testConnection())->toBeFalse();
});

test('test connection returns false when access token is missing', function () {
    $gateway = PaymentGateway::factory()->mercadoPago()->create(['credentials' => []]);

    expect((new MercadoPagoDriver($gateway))->testConnection())->toBeFalse();
});
