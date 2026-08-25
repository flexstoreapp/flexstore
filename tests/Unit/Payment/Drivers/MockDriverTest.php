<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Payment\Drivers\MockDriver;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;

covers(MockDriver::class);

uses()->group('payment');

test('verify payment returns current status when not always succeed', function () {
    $driver = new MockDriver();

    $verification = $driver->verifyPayment('mock_txn_123', PaymentStatus::Unpaid);

    expect($verification)->toBeInstanceOf(VerificationResult::class)
        ->and($verification->status)->toBe(PaymentStatus::Unpaid);
});

test('refunds payment', function () {
    $driver = new MockDriver();

    $refund = $driver->refund(new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: null,
        reason: 'Customer request',
    ));

    expect($refund)->toBeInstanceOf(RefundResult::class)
        ->and($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->amount)->toBe('50.0000')
        ->and($refund->gatewayReference)->toStartWith('mock_refund_');
});

test('parses webhook', function () {
    $request = new Illuminate\Http\Request(['gateway_reference' => 'mock_123']);
    $driver = new MockDriver();

    $event = $driver->parseWebhook($request);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->type)->toBe('mock.event')
        ->and($event->gatewayPaymentReference)->toBe('mock_123');
});

test('verify webhook returns true', function () {
    $request = new Illuminate\Http\Request();
    $driver = new MockDriver();

    expect($driver->verifyWebhook($request))->toBeTrue();
});

test('supports refunds', function () {
    $driver = new MockDriver();

    expect($driver->supportsRefunds())->toBeTrue();
});
