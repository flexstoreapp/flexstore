<?php

declare(strict_types=1);

use App\DTOs\PaymentSettlement;
use App\Enums\PaymentStatus;

covers(PaymentSettlement::class);

uses()->group('dtos', 'payment');

test('stores all fields', function () {
    $settlement = new PaymentSettlement(
        status: PaymentStatus::Paid,
        gatewayReference: 'pi_123',
        paymentMethod: 'card',
        paymentMethodDetails: ['brand' => 'visa', 'last4' => '4242'],
    );

    expect($settlement->status)->toBe(PaymentStatus::Paid)
        ->and($settlement->gatewayReference)->toBe('pi_123')
        ->and($settlement->paymentMethod)->toBe('card')
        ->and($settlement->paymentMethodDetails)->toBe(['brand' => 'visa', 'last4' => '4242'])
        ->and($settlement->failureReason)->toBeNull();
});

test('status defaults required, optional fields default to null', function () {
    $settlement = new PaymentSettlement(status: PaymentStatus::Failed);

    expect($settlement->status)->toBe(PaymentStatus::Failed)
        ->and($settlement->gatewayReference)->toBeNull()
        ->and($settlement->paymentMethod)->toBeNull()
        ->and($settlement->paymentMethodDetails)->toBeNull()
        ->and($settlement->failureReason)->toBeNull();
});
