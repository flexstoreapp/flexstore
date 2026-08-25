<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

use App\Enums\PaymentStatus;

final readonly class SessionResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public PaymentStatus $status,
        public string $redirectUrl,
        public ?string $gatewayReference = null,
        public array $payload = [],
        public ?string $failureReason = null,
    ) {
    }
}
