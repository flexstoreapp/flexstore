<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

use App\Enums\PaymentStatus;

final readonly class VerificationResult
{
    /**
     * @param  array<string, mixed>|null  $paymentMethodDetails
     */
    public function __construct(
        public PaymentStatus $status,
        public ?string $gatewayReference = null,
        public ?string $paymentMethod = null,
        public ?array $paymentMethodDetails = null,
    ) {
    }
}
