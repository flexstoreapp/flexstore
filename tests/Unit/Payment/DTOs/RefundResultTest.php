<?php

declare(strict_types=1);

use App\Enums\RefundStatus;
use App\Payment\DTOs\RefundResult;

covers(RefundResult::class);

uses()->group('payment', 'dtos');

test('creates with required fields only', function () {
    $refund = new RefundResult(
        status: RefundStatus::Completed,
        amount: '25.0000',
    );

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->amount)->toBe('25.0000')
        ->and($refund->gatewayReference)->toBeNull()
        ->and($refund->payload)->toBe([])
        ->and($refund->failureReason)->toBeNull();
});

test('creates with all fields', function () {
    $refund = new RefundResult(
        status: RefundStatus::Failed,
        amount: '15.0000',
        gatewayReference: 're_456',
        payload: ['refund_id' => 're_456'],
        failureReason: 'Insufficient funds',
    );

    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->amount)->toBe('15.0000')
        ->and($refund->gatewayReference)->toBe('re_456')
        ->and($refund->payload)->toBe(['refund_id' => 're_456'])
        ->and($refund->failureReason)->toBe('Insufficient funds');
});
