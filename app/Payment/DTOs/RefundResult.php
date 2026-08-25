<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

use App\Enums\RefundStatus;

final readonly class RefundResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public RefundStatus $status,
        public string $amount,
        public ?string $gatewayReference = null,
        public array $payload = [],
        public ?string $failureReason = null,
    ) {
    }
}
