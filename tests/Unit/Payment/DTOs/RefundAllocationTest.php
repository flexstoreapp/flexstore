<?php

declare(strict_types=1);

use App\Payment\DTOs\RefundAllocation;

covers(RefundAllocation::class);

uses()->group('payment', 'dtos');

test('stores transaction id, gateway reference, and amount', function () {
    $allocation = new RefundAllocation(
        transactionId: 101,
        gatewayReference: 'pi_abc',
        amount: '50.0000',
    );

    expect($allocation->transactionId)->toBe(101)
        ->and($allocation->gatewayReference)->toBe('pi_abc')
        ->and($allocation->amount)->toBe('50.0000');
});

test('allows null gateway reference', function () {
    $allocation = new RefundAllocation(
        transactionId: 5,
        gatewayReference: null,
        amount: '10.0000',
    );

    expect($allocation->gatewayReference)->toBeNull();
});

test('properties are readonly', function () {
    $allocation = new RefundAllocation(1, null, '1.0000');

    expect(fn () => $allocation->amount = '2.0000')->toThrow(Error::class);
});
