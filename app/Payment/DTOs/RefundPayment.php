<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

final readonly class RefundPayment
{
    public function __construct(
        public string $amount,
        public string $currencyCode,
        public ?string $gatewayReference,
        public ?int $orderId = null,
        public ?string $reason = null,
        public ?string $idempotencyKey = null,
    ) {
    }

    public static function fromAllocation(
        RefundAllocation $allocation,
        string $currencyCode,
        ?int $orderId = null,
        ?string $reason = null,
        ?string $idempotencyKey = null,
    ): self {
        return new self(
            amount: $allocation->amount,
            currencyCode: $currencyCode,
            gatewayReference: $allocation->gatewayReference,
            orderId: $orderId,
            reason: $reason,
            idempotencyKey: $idempotencyKey,
        );
    }
}
