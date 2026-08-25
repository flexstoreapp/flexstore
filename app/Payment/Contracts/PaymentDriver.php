<?php

declare(strict_types=1);

namespace App\Payment\Contracts;

use App\Enums\PaymentStatus;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\SessionResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;
use Illuminate\Http\Request;

interface PaymentDriver
{
    public function createSession(CreateSession $session): SessionResult;

    public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult;

    public function refund(RefundPayment $refund): RefundResult;

    public function verifyWebhook(Request $request): bool;

    public function parseWebhook(Request $request): WebhookEvent;

    public function testConnection(): bool;

    public function supportsRefunds(): bool;

    public function isManual(): bool;
}
