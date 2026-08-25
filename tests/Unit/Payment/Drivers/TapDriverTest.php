<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Currency;
use App\Models\PaymentGateway;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\TapDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

covers(TapDriver::class);

uses()->group('payment', 'tap');

function makeTapGateway(array $credentials = []): PaymentGateway
{
    return PaymentGateway::factory()->tap()->create([
        'credentials' => [
            'public_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'checkout_mode' => CheckoutMode::Hosted->value,
            ...$credentials,
        ],
    ]);
}

function makeTapSession(array $overrides = []): CreateSession
{
    return new CreateSession(...[
        'internalReference' => 'tap_session_1',
        'amount' => '100.0000',
        'currencyCode' => 'USD',
        'redirectUrls' => new RedirectUrls(
            returnUrl: 'https://example.com/success',
            cancelUrl: 'https://example.com/cancel',
            failureUrl: 'https://example.com/checkout',
        ),
        'callbackUrls' => new CallbackUrls(webhookUrl: 'https://example.com/webhook'),
        'customerEmail' => 'customer@example.com',
        'description' => 'Test order',
        'metadata' => ['payment_session_id' => 'tap_session_1'],
        'billingAddress' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
        ...$overrides,
    ]);
}

test('implements PaymentDriver interface', function () {
    expect(new TapDriver(makeTapGateway()))->toBeInstanceOf(PaymentDriver::class);
});

test('supports refunds', function () {
    expect((new TapDriver(makeTapGateway()))->supportsRefunds())->toBeTrue();
});

test('is manual returns false', function () {
    expect((new TapDriver(makeTapGateway()))->isManual())->toBeFalse();
});

test('creates hosted session with generic card source and redirect url', function () {
    Http::fake([
        'api.tap.company/v2/charges' => Http::response([
            'id' => 'chg_123',
            'status' => 'INITIATED',
            'transaction' => ['url' => 'https://tap.company/pay/chg_123'],
        ]),
    ]);

    $result = (new TapDriver(makeTapGateway()))->createSession(makeTapSession());

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->redirectUrl)->toBe('https://tap.company/pay/chg_123')
        ->and($result->gatewayReference)->toBe('chg_123')
        ->and($result->payload['tap_charge_id'])->toBe('chg_123')
        ->and($result->payload)->not->toHaveKey('return_url');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.tap.company/v2/charges'
            && $request['amount'] === 100.0
            && $request['currency'] === 'USD'
            && $request['source']['id'] === 'src_all'
            && $request['reference']['transaction'] === 'tap_session_1'
            && $request['redirect']['url'] === 'https://example.com/success'
            && $request['post']['url'] === 'https://example.com/webhook'
            && $request['customer']['first_name'] === 'Jane'
            && $request['customer']['email'] === 'customer@example.com'
            && $request['metadata']['payment_session_id'] === 'tap_session_1';
    });
});

test('creates embedded session using the card token as source', function () {
    Http::fake([
        'api.tap.company/v2/charges' => Http::response([
            'id' => 'chg_456',
            'status' => 'INITIATED',
            'transaction' => ['url' => 'https://tap.company/3ds/chg_456'],
        ]),
    ]);

    $gateway = makeTapGateway(['checkout_mode' => CheckoutMode::Embedded->value]);

    $result = (new TapDriver($gateway))->createSession(makeTapSession([
        'providerOptions' => ['card_token' => 'tok_abc'],
    ]));

    expect($result->status)->toBe(PaymentStatus::Unpaid)
        ->and($result->redirectUrl)->toBe('https://example.com/checkout')
        ->and($result->gatewayReference)->toBe('chg_456')
        ->and($result->payload['return_url'])->toBe('https://tap.company/3ds/chg_456');

    Http::assertSent(fn ($request) => $request['source']['id'] === 'tok_abc');
});

test('embedded session fails when card token is missing', function () {
    $gateway = makeTapGateway(['checkout_mode' => CheckoutMode::Embedded->value]);

    $result = (new TapDriver($gateway))->createSession(makeTapSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->redirectUrl)->toBe('https://example.com/checkout')
        ->and($result->failureReason)->toBe('Card token is required for embedded checkout.');
});

test('create session returns failure with the api error message', function () {
    Http::fake([
        'api.tap.company/v2/charges' => Http::response([
            'errors' => [['code' => '1105', 'description' => 'Invalid currency code']],
        ], 400),
    ]);

    $result = (new TapDriver(makeTapGateway()))->createSession(makeTapSession());

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->redirectUrl)->toBe('https://example.com/checkout')
        ->and($result->failureReason)->toBe('Invalid currency code');
});

test('formats amount to the currency decimal places', function () {
    Currency::query()->firstOrCreate(
        ['code' => 'KWD'],
        ['symbol' => 'KD', 'exchange_rate' => 1, 'decimal_places' => 3, 'is_active' => true, 'symbol_position' => 'before', 'thousands_separator' => ',', 'decimal_separator' => '.'],
    );

    Http::fake([
        'api.tap.company/v2/charges' => Http::response([
            'id' => 'chg_kwd',
            'status' => 'INITIATED',
            'transaction' => ['url' => 'https://tap.company/pay/chg_kwd'],
        ]),
    ]);

    (new TapDriver(makeTapGateway()))->createSession(makeTapSession(['amount' => '100', 'currencyCode' => 'KWD']));

    Http::assertSent(fn ($request) => $request['amount'] === 100.0 && $request['currency'] === 'KWD');
});

test('verify payment maps captured to paid and extracts card details', function () {
    Http::fake([
        'api.tap.company/v2/charges/chg_123' => Http::response([
            'id' => 'chg_123',
            'status' => 'CAPTURED',
            'source' => ['payment_method' => 'CARD'],
            'card' => ['brand' => 'VISA', 'last_four' => '4242'],
        ]),
    ]);

    $result = (new TapDriver(makeTapGateway()))->verifyPayment('chg_123', PaymentStatus::Unpaid);

    expect($result->status)->toBe(PaymentStatus::Paid)
        ->and($result->gatewayReference)->toBe('chg_123')
        ->and($result->paymentMethod)->toBe('CARD')
        ->and($result->paymentMethodDetails)->toBe(['brand' => 'VISA', 'last4' => '4242']);
});

test('verify payment maps declined to failed', function () {
    Http::fake([
        'api.tap.company/v2/charges/chg_9' => Http::response(['id' => 'chg_9', 'status' => 'DECLINED']),
    ]);

    expect((new TapDriver(makeTapGateway()))->verifyPayment('chg_9', PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Failed);
});

test('verify payment returns current status without a reference', function () {
    expect((new TapDriver(makeTapGateway()))->verifyPayment(null, PaymentStatus::Unpaid)->status)
        ->toBe(PaymentStatus::Unpaid);
});

test('full refund is marked completed', function () {
    Http::fake([
        'api.tap.company/v2/refunds' => Http::response(['id' => 're_1', 'status' => 'REFUNDED']),
    ]);

    $refund = (new TapDriver(makeTapGateway()))->refund(new RefundPayment(
        amount: '100.00',
        currencyCode: 'USD',
        gatewayReference: 'chg_123',
        reason: 'Customer request',
    ));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->gatewayReference)->toBe('re_1')
        ->and($refund->payload['tap_charge_id'])->toBe('chg_123');

    Http::assertSent(fn ($request) => $request['charge_id'] === 'chg_123'
        && $request['amount'] === 100.0
        && $request['currency'] === 'USD'
        && $request['reason'] === 'Customer request');
});

test('pending refund is marked pending', function () {
    Http::fake([
        'api.tap.company/v2/refunds' => Http::response(['id' => 're_2', 'status' => 'PENDING']),
    ]);

    $refund = (new TapDriver(makeTapGateway()))->refund(new RefundPayment(
        amount: '40.00',
        currencyCode: 'USD',
        gatewayReference: 'chg_123',
    ));

    expect($refund->status)->toBe(RefundStatus::Pending);
});

test('refund fails when the api rejects it', function () {
    Http::fake([
        'api.tap.company/v2/refunds' => Http::response([
            'errors' => [['code' => '1108', 'description' => 'Refund exceeds captured amount']],
        ], 400),
    ]);

    $refund = (new TapDriver(makeTapGateway()))->refund(new RefundPayment(
        amount: '999.00',
        currencyCode: 'USD',
        gatewayReference: 'chg_123',
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('Refund exceeds captured amount');
});

test('refund fails without a gateway reference', function () {
    $refund = (new TapDriver(makeTapGateway()))->refund(new RefundPayment(
        amount: '10.00',
        currencyCode: 'USD',
        gatewayReference: null,
    ));

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->failureReason)->toBe('No gateway reference found for this order.');
});

function tapWebhookRequest(array $payload, ?string $secretKey = 'sk_test_123'): Request
{
    $content = json_encode($payload);
    $server = [];

    if ($secretKey !== null) {
        $hashString = implode('', [
            'x_id' . ($payload['id'] ?? ''),
            'x_amount' . number_format((float) ($payload['amount'] ?? 0), 2, '.', ''),
            'x_currency' . ($payload['currency'] ?? ''),
            'x_gateway_reference' . ($payload['reference']['gateway'] ?? ''),
            'x_payment_reference' . ($payload['reference']['payment'] ?? ''),
            'x_status' . ($payload['status'] ?? ''),
            'x_created' . ($payload['transaction']['created'] ?? ''),
        ]);
        $server['HTTP_HASHSTRING'] = hash_hmac('sha256', $hashString, $secretKey);
    }

    return Request::create('https://example.com/webhook', 'POST', [], [], [], $server, $content);
}

test('verify webhook accepts a valid hashstring', function () {
    $payload = [
        'id' => 'chg_123',
        'amount' => 100,
        'currency' => 'USD',
        'status' => 'CAPTURED',
        'reference' => ['gateway' => 'gw_1', 'payment' => 'pay_1'],
        'transaction' => ['created' => '1700000000000'],
    ];

    expect((new TapDriver(makeTapGateway()))->verifyWebhook(tapWebhookRequest($payload)))->toBeTrue();
});

test('verify webhook rejects a tampered hashstring', function () {
    $request = tapWebhookRequest(['id' => 'chg_123', 'amount' => 100, 'currency' => 'USD', 'status' => 'CAPTURED']);
    $request->headers->set('hashstring', 'deadbeef');

    expect((new TapDriver(makeTapGateway()))->verifyWebhook($request))->toBeFalse();
});

test('verify webhook rejects a missing hashstring header', function () {
    $request = tapWebhookRequest(['id' => 'chg_123', 'amount' => 100, 'currency' => 'USD'], secretKey: null);

    expect((new TapDriver(makeTapGateway()))->verifyWebhook($request))->toBeFalse();
});

test('verify webhook fails when the secret key is missing', function () {
    $gateway = PaymentGateway::factory()->tap()->create(['credentials' => ['public_key' => 'pk_test_123']]);
    $request = tapWebhookRequest(['id' => 'chg_123', 'amount' => 100, 'currency' => 'USD']);

    expect((new TapDriver($gateway))->verifyWebhook($request))->toBeFalse();
});

test('parse webhook maps a captured charge to paid', function () {
    $request = tapWebhookRequest([
        'object' => 'charge',
        'id' => 'chg_123',
        'amount' => 100,
        'currency' => 'USD',
        'status' => 'CAPTURED',
        'metadata' => ['payment_session_id' => 'tap_session_1'],
        'source' => ['payment_method' => 'CARD'],
        'card' => ['brand' => 'MASTERCARD', 'last_four' => '1111'],
    ]);

    $event = (new TapDriver(makeTapGateway()))->parseWebhook($request);

    expect($event->type)->toBe('charge.captured')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('tap_session_1')
        ->and($event->gatewayPaymentReference)->toBe('chg_123')
        ->and($event->paymentMethod)->toBe('CARD')
        ->and($event->paymentMethodDetails)->toBe(['brand' => 'MASTERCARD', 'last4' => '1111']);
});

test('parse webhook maps a failed charge', function () {
    $request = tapWebhookRequest([
        'object' => 'charge',
        'id' => 'chg_9',
        'amount' => 100,
        'currency' => 'USD',
        'status' => 'DECLINED',
    ]);

    $event = (new TapDriver(makeTapGateway()))->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::Failed);
});

test('parse webhook resolves a full external refund', function () {
    Http::fake([
        'api.tap.company/v2/charges/chg_123' => Http::response(['id' => 'chg_123', 'amount' => 100]),
    ]);

    $request = tapWebhookRequest([
        'object' => 'refund',
        'id' => 're_1',
        'amount' => 100,
        'currency' => 'USD',
        'status' => 'REFUNDED',
        'charge' => ['id' => 'chg_123'],
    ]);

    $event = (new TapDriver(makeTapGateway()))->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::Refunded)
        ->and($event->gatewayPaymentReference)->toBe('chg_123')
        ->and($event->gatewayRefundReference)->toBe('re_1')
        ->and($event->cumulativeRefundTotal)->toBe('100.00')
        ->and($event->isRefundEvent())->toBeTrue();
});

test('parse webhook resolves a partial external refund', function () {
    Http::fake([
        'api.tap.company/v2/charges/chg_123' => Http::response(['id' => 'chg_123', 'amount' => 100]),
    ]);

    $request = tapWebhookRequest([
        'object' => 'refund',
        'id' => 're_2',
        'amount' => 40,
        'currency' => 'USD',
        'status' => 'REFUNDED',
        'charge' => ['id' => 'chg_123'],
    ]);

    $event = (new TapDriver(makeTapGateway()))->parseWebhook($request);

    expect($event->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($event->cumulativeRefundTotal)->toBe('40.00');
});

test('test connection returns true when the key authenticates', function () {
    Http::fake(['api.tap.company/v2/charges/*' => Http::response(['message' => 'not found'], 404)]);

    expect((new TapDriver(makeTapGateway()))->testConnection())->toBeTrue();
});

test('test connection returns true when the key is valid but scoped', function () {
    Http::fake(['api.tap.company/v2/charges/*' => Http::response([], 403)]);

    expect((new TapDriver(makeTapGateway()))->testConnection())->toBeTrue();
});

test('test connection returns false when the credentials are rejected', function () {
    Http::fake(['api.tap.company/v2/charges/*' => Http::response([], 401)]);

    expect((new TapDriver(makeTapGateway()))->testConnection())->toBeFalse();
});

test('test connection returns false when the secret key is missing', function () {
    $gateway = PaymentGateway::factory()->tap()->create(['credentials' => ['public_key' => 'pk_test_123']]);

    expect((new TapDriver($gateway))->testConnection())->toBeFalse();
});
