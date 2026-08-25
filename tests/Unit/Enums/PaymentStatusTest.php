<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;

covers(PaymentStatus::class);

uses()->group('enums');

test('eligibleForReview returns the statuses that can be reviewed', function () {
    expect(PaymentStatus::eligibleForReview())->toBe([
        PaymentStatus::PartiallyPaid,
        PaymentStatus::Paid,
        PaymentStatus::PartiallyRefunded,
    ]);
});

test('eligibleForReview excludes unpaid, refunded, failed, and canceled', function () {
    $eligible = PaymentStatus::eligibleForReview();

    expect($eligible)->not->toContain(PaymentStatus::Unpaid)
        ->and($eligible)->not->toContain(PaymentStatus::Refunded)
        ->and($eligible)->not->toContain(PaymentStatus::Failed)
        ->and($eligible)->not->toContain(PaymentStatus::Canceled);
});
