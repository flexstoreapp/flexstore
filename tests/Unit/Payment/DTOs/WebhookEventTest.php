<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Payment\DTOs\WebhookEvent;

covers(WebhookEvent::class);

uses()->group('payment', 'dtos');

test('creates with required fields only', function () {
    $event = new WebhookEvent(
        type: 'checkout.session.completed',
    );

    expect($event->type)->toBe('checkout.session.completed')
        ->and($event->status)->toBeNull()
        ->and($event->paymentSessionId)->toBeNull()
        ->and($event->gatewayPaymentReference)->toBeNull()
        ->and($event->payload)->toBe([]);
});

test('creates with all fields', function () {
    $event = new WebhookEvent(
        type: 'payment_intent.succeeded',
        status: PaymentStatus::Paid,
        paymentSessionId: 'cs_abc',
        gatewayPaymentReference: 'pi_789',
        payload: ['amount' => '100.0000'],
        gatewayRefundReference: 're_123',
    );

    expect($event->type)->toBe('payment_intent.succeeded')
        ->and($event->status)->toBe(PaymentStatus::Paid)
        ->and($event->paymentSessionId)->toBe('cs_abc')
        ->and($event->gatewayPaymentReference)->toBe('pi_789')
        ->and($event->payload)->toBe(['amount' => '100.0000'])
        ->and($event->gatewayRefundReference)->toBe('re_123');
});

test('should confirm payment session returns true when status and paymentSessionId are present', function () {
    $event = new WebhookEvent(
        type: 'payment_intent.succeeded',
        status: PaymentStatus::Paid,
        paymentSessionId: 'cs_abc',
    );

    expect($event->shouldConfirmPaymentSession())->toBeTrue();
});

test('should confirm payment session returns false when status is null', function () {
    $event = new WebhookEvent(
        type: 'unknown.event',
        paymentSessionId: 'cs_abc',
    );

    expect($event->shouldConfirmPaymentSession())->toBeFalse();
});

test('should confirm payment session returns false when paymentSessionId is null', function () {
    $event = new WebhookEvent(
        type: 'payment_intent.succeeded',
        status: PaymentStatus::Paid,
    );

    expect($event->shouldConfirmPaymentSession())->toBeFalse();
});

test('should confirm payment session returns false when both are null', function () {
    $event = new WebhookEvent(
        type: 'unknown.event',
    );

    expect($event->shouldConfirmPaymentSession())->toBeFalse();
});

test('should confirm payment session returns false for refunded status', function () {
    $event = new WebhookEvent(
        type: 'refund.processed',
        status: PaymentStatus::Refunded,
        paymentSessionId: 'cs_abc',
    );

    expect($event->shouldConfirmPaymentSession())->toBeFalse();
});

test('should confirm payment session returns false for partially refunded status', function () {
    $event = new WebhookEvent(
        type: 'refund.processed',
        status: PaymentStatus::PartiallyRefunded,
        paymentSessionId: 'cs_abc',
    );

    expect($event->shouldConfirmPaymentSession())->toBeFalse();
});

test('is refund event returns true with gateway reference', function () {
    $event = new WebhookEvent(
        type: 'charge.refunded',
        gatewayPaymentReference: 'pi_789',
        cumulativeRefundTotal: '50.0000',
    );

    expect($event->isRefundEvent())->toBeTrue();
});

test('is refund event returns true with payment session id', function () {
    $event = new WebhookEvent(
        type: 'charge.refunded',
        paymentSessionId: 'cs_abc',
        cumulativeRefundTotal: '50.0000',
    );

    expect($event->isRefundEvent())->toBeTrue();
});

test('is refund event returns false without any identifier', function () {
    $event = new WebhookEvent(
        type: 'charge.refunded',
        cumulativeRefundTotal: '50.0000',
    );

    expect($event->isRefundEvent())->toBeFalse();
});

test('is refund event returns false when refund amount is null', function () {
    $event = new WebhookEvent(
        type: 'charge.refunded',
        gatewayPaymentReference: 'pi_789',
    );

    expect($event->isRefundEvent())->toBeFalse();
});

test('is refund event returns false when refund amount is zero', function () {
    $event = new WebhookEvent(
        type: 'charge.refunded',
        gatewayPaymentReference: 'pi_789',
        cumulativeRefundTotal: '0.0000',
    );

    expect($event->isRefundEvent())->toBeFalse();
});
