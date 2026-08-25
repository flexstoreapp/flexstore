<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\CheckoutSession;
use App\Payment\Drivers\CodDriver;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\SessionResult;

covers(CodDriver::class);

uses()->group('payment');

test('creates session with unpaid status', function () {
    $checkoutSession = CheckoutSession::factory()->create();
    $driver = new CodDriver();

    $session = $driver->createSession(new CreateSession(
        internalReference: $checkoutSession->id,
        amount: $checkoutSession->total,
        currencyCode: (string) $checkoutSession->currency_code,
        customerEmail: $checkoutSession->customer_email,
        description: 'Test order',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/checkout/success', cancelUrl: 'https://example.com/cancel', failureUrl: 'https://example.com/checkout'),
        callbackUrls: new CallbackUrls(),
        metadata: ['payment_session_id' => $checkoutSession->id],
    ));

    expect($session)->toBeInstanceOf(SessionResult::class)
        ->and($session->status)->toBe(PaymentStatus::Unpaid)
        ->and($session->gatewayReference)->toBeNull()
        ->and($session->redirectUrl)->toContain('checkout/success');
});

test('refund throws exception', function () {
    $driver = new CodDriver();

    $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: null,
    ));
})->throws(RuntimeException::class, 'Cash on delivery does not support refunds.');

test('parse webhook throws exception', function () {
    $request = new Illuminate\Http\Request();
    $driver = new CodDriver();

    $driver->parseWebhook($request);
})->throws(RuntimeException::class, 'Cash on delivery does not support webhooks.');

test('verify webhook returns false', function () {
    $request = new Illuminate\Http\Request();
    $driver = new CodDriver();

    expect($driver->verifyWebhook($request))->toBeFalse();
});

test('does not support refunds', function () {
    $driver = new CodDriver();

    expect($driver->supportsRefunds())->toBeFalse();
});

test('is manual', function () {
    $driver = new CodDriver();

    expect($driver->isManual())->toBeTrue();
});

test('test connection returns true', function () {
    $driver = new CodDriver();

    expect($driver->testConnection())->toBeTrue();
});
