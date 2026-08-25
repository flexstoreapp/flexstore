<?php

declare(strict_types=1);

namespace App\Payment\Drivers;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\SessionResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class MockDriver implements PaymentDriver
{
    public function __construct(private bool $alwaysSucceed = false)
    {
    }

    public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
    {
        return new VerificationResult(
            status: $this->alwaysSucceed ? PaymentStatus::Paid : $currentStatus,
        );
    }

    public function createSession(CreateSession $session): SessionResult
    {
        $isFailed = ! $this->alwaysSucceed && random_int(1, 10) === 1;

        return new SessionResult(
            status: $isFailed ? PaymentStatus::Failed : PaymentStatus::Paid,
            redirectUrl: $session->redirectUrls->returnUrl,
            gatewayReference: 'mock_' . Str::random(24),
            payload: [
                'message' => $isFailed
                    ? 'Mock payment simulated failure.'
                    : 'Mock payment processed.',
            ],
            failureReason: $isFailed ? 'Insufficient funds' : null,
        );
    }

    public function refund(RefundPayment $refund): RefundResult
    {
        return new RefundResult(
            status: RefundStatus::Completed,
            amount: BigDecimal::of($refund->amount)->toScale(4, RoundingMode::HalfUp)->toString(),
            gatewayReference: 'mock_refund_' . Str::random(24),
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        return true;
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $refundAmount = $request->input('refund_amount');

        return new WebhookEvent(
            type: 'mock.event',
            status: $request->has('status') ? PaymentStatus::tryFrom($request->input('status')) : null,
            paymentSessionId: $request->input('payment_session_id'),
            gatewayPaymentReference: $request->input('gateway_reference'),
            payload: $request->all(),
            cumulativeRefundTotal: is_string($refundAmount) ? $refundAmount : null,
        );
    }

    public function testConnection(): bool
    {
        return $this->alwaysSucceed;
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function isManual(): bool
    {
        return false;
    }
}
