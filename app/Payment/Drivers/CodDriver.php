<?php

declare(strict_types=1);

namespace App\Payment\Drivers;

use App\Enums\PaymentStatus;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\SessionResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;
use Illuminate\Http\Request;
use RuntimeException;

final readonly class CodDriver implements PaymentDriver
{
    public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
    {
        return new VerificationResult(status: $currentStatus);
    }

    public function createSession(CreateSession $session): SessionResult
    {
        return new SessionResult(
            status: PaymentStatus::Unpaid,
            redirectUrl: $session->redirectUrls->returnUrl,
            payload: [
                'message' => 'Cash on delivery order placed.',
            ],
        );
    }

    public function refund(RefundPayment $refund): RefundResult
    {
        throw new RuntimeException(__('Cash on delivery does not support refunds.'));
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        throw new RuntimeException(__('Cash on delivery does not support webhooks.'));
    }

    public function testConnection(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    public function isManual(): bool
    {
        return true;
    }
}
