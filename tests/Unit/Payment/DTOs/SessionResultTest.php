<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Payment\DTOs\SessionResult;

covers(SessionResult::class);

uses()->group('payment', 'dtos');

test('creates with required fields only', function () {
    $session = new SessionResult(
        status: PaymentStatus::Unpaid,
        redirectUrl: 'https://example.com/checkout',
    );

    expect($session->status)->toBe(PaymentStatus::Unpaid)
        ->and($session->redirectUrl)->toBe('https://example.com/checkout')
        ->and($session->gatewayReference)->toBeNull()
        ->and($session->payload)->toBe([])
        ->and($session->failureReason)->toBeNull();
});

test('creates with all fields', function () {
    $session = new SessionResult(
        status: PaymentStatus::Failed,
        redirectUrl: 'https://example.com/checkout',
        gatewayReference: 'ref_456',
        payload: ['session_id' => 'sess_123'],
        failureReason: 'Card declined',
    );

    expect($session->status)->toBe(PaymentStatus::Failed)
        ->and($session->redirectUrl)->toBe('https://example.com/checkout')
        ->and($session->gatewayReference)->toBe('ref_456')
        ->and($session->payload)->toBe(['session_id' => 'sess_123'])
        ->and($session->failureReason)->toBe('Card declined');
});

test('creates with embedded checkout fields', function () {
    $session = new SessionResult(
        status: PaymentStatus::Unpaid,
        redirectUrl: 'https://example.com/checkout',
        gatewayReference: 'cs_123',
        payload: [
            'session_id' => 'cs_123',
            'client_secret' => 'cs_123_secret_abc',
        ],
    );

    expect($session->payload['client_secret'])->toBe('cs_123_secret_abc');
});
