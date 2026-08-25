<?php

declare(strict_types=1);

use App\Payment\DTOs\RefundAllocation;
use App\Payment\DTOs\RefundPayment;

covers(RefundPayment::class);

uses()->group('payment', 'dtos');

test('can be constructed directly', function () {
    $refund = new RefundPayment(
        amount: '50.0000',
        currencyCode: 'USD',
        gatewayReference: 'pi_test_123',
        orderId: 42,
        reason: 'Customer request',
        idempotencyKey: 'refund_42',
    );

    expect($refund->amount)->toBe('50.0000')
        ->and($refund->currencyCode)->toBe('USD')
        ->and($refund->gatewayReference)->toBe('pi_test_123')
        ->and($refund->orderId)->toBe(42)
        ->and($refund->reason)->toBe('Customer request')
        ->and($refund->idempotencyKey)->toBe('refund_42');
});

test('can be constructed with all parameters', function () {
    $refund = new RefundPayment(
        amount: '100.0000',
        currencyCode: 'EUR',
        gatewayReference: 'pi_order_ref',
        orderId: 42,
        reason: 'Damaged item',
        idempotencyKey: 'refund_1',
    );

    expect($refund->amount)->toBe('100.0000')
        ->and($refund->currencyCode)->toBe('EUR')
        ->and($refund->gatewayReference)->toBe('pi_order_ref')
        ->and($refund->orderId)->toBe(42)
        ->and($refund->reason)->toBe('Damaged item')
        ->and($refund->idempotencyKey)->toBe('refund_1');
});

test('optional fields default to null', function () {
    $refund = new RefundPayment(
        amount: '100.0000',
        currencyCode: 'USD',
        gatewayReference: null,
    );

    expect($refund->reason)->toBeNull()
        ->and($refund->idempotencyKey)->toBeNull()
        ->and($refund->orderId)->toBeNull();
});

test('gateway reference can be null', function () {
    $refund = new RefundPayment(
        amount: '25.0000',
        currencyCode: 'USD',
        gatewayReference: null,
    );

    expect($refund->gatewayReference)->toBeNull();
});

test('fromAllocation maps allocation and metadata into refund payment', function () {
    $allocation = new RefundAllocation(
        transactionId: 11,
        gatewayReference: 'pi_abc',
        amount: '25.0000',
    );

    $refund = RefundPayment::fromAllocation(
        allocation: $allocation,
        currencyCode: 'USD',
        orderId: 42,
        reason: 'Customer request',
        idempotencyKey: 'refund_42',
    );

    expect($refund->amount)->toBe('25.0000')
        ->and($refund->currencyCode)->toBe('USD')
        ->and($refund->gatewayReference)->toBe('pi_abc')
        ->and($refund->orderId)->toBe(42)
        ->and($refund->reason)->toBe('Customer request')
        ->and($refund->idempotencyKey)->toBe('refund_42');
});

test('fromAllocation defaults optional parameters to null', function () {
    $allocation = new RefundAllocation(1, null, '5.0000');

    $refund = RefundPayment::fromAllocation($allocation, 'USD');

    expect($refund->orderId)->toBeNull()
        ->and($refund->reason)->toBeNull()
        ->and($refund->idempotencyKey)->toBeNull()
        ->and($refund->gatewayReference)->toBeNull();
});
