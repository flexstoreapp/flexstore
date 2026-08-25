<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Payment\DTOs\VerificationResult;

covers(VerificationResult::class);

uses()->group('payment', 'dtos');

test('creates with required fields only', function () {
    $verification = new VerificationResult(
        status: PaymentStatus::Unpaid,
    );

    expect($verification->status)->toBe(PaymentStatus::Unpaid)
        ->and($verification->gatewayReference)->toBeNull();
});

test('creates with all fields', function () {
    $verification = new VerificationResult(
        status: PaymentStatus::Paid,
        gatewayReference: 'pi_123',
    );

    expect($verification->status)->toBe(PaymentStatus::Paid)
        ->and($verification->gatewayReference)->toBe('pi_123');
});
