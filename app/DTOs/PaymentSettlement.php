<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PaymentStatus;

final readonly class PaymentSettlement
{
    /**
     * @param  array<string, mixed>|null  $paymentMethodDetails
     */
    public function __construct(
        public PaymentStatus $status,
        public ?string $gatewayReference = null,
        public ?string $paymentMethod = null,
        public ?array $paymentMethodDetails = null,
        public ?string $failureReason = null,
    ) {
    }
}
