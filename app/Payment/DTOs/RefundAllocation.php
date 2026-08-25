<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

final readonly class RefundAllocation
{
    public function __construct(
        public int $transactionId,
        public ?string $gatewayReference,
        public string $amount,
    ) {
    }
}
