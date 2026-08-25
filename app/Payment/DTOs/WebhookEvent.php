<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

use App\Enums\PaymentStatus;
use Brick\Math\BigDecimal;

final readonly class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $paymentMethodDetails
     */
    public function __construct(
        public string $type,
        public ?PaymentStatus $status = null,
        public ?string $paymentSessionId = null,
        public ?string $gatewayPaymentReference = null,
        public ?string $gatewayOrderReference = null,
        public array $payload = [],
        public ?string $cumulativeRefundTotal = null,
        public ?string $gatewayRefundReference = null,
        public ?string $paymentMethod = null,
        public ?array $paymentMethodDetails = null,
    ) {
    }

    public function isRefundEvent(): bool
    {
        return $this->cumulativeRefundTotal !== null
            && BigDecimal::of($this->cumulativeRefundTotal)->isPositive()
            && ($this->gatewayPaymentReference !== null || $this->paymentSessionId !== null);
    }

    public function shouldConfirmPaymentSession(): bool
    {
        return $this->status instanceof PaymentStatus
            && $this->paymentSessionId !== null
            && ! in_array($this->status, [PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true);
    }
}
