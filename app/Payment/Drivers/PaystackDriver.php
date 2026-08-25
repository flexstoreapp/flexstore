<?php

declare(strict_types=1);

namespace App\Payment\Drivers;

use App\Enums\CheckoutMode;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\PaymentGateway;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\SessionResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final readonly class PaystackDriver implements PaymentDriver
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {
    }

    public function createSession(CreateSession $session): SessionResult
    {
        if ($session->customerEmail === null || $session->customerEmail === '') {
            return new SessionResult(
                status: PaymentStatus::Failed,
                redirectUrl: $session->redirectUrls->failureUrl ?? '',
                failureReason: __('Customer email is required for Paystack payments.'),
            );
        }

        try {
            $metadata = $session->metadata;

            if ($session->redirectUrls->cancelUrl !== null) {
                $metadata['cancel_action'] = $session->redirectUrls->cancelUrl;
            }

            $response = $this->client()->post('/transaction/initialize', [
                'email' => $session->customerEmail,
                'amount' => $this->toSubunit($session->amount),
                'currency' => mb_strtoupper($session->currencyCode),
                'reference' => $session->internalReference,
                'callback_url' => $session->redirectUrls->returnUrl,
                'metadata' => $metadata,
            ]);

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('message', __('Failed to initialize Paystack transaction.')),
                );
            }

            if ($this->isEmbeddedMode()) {
                return new SessionResult(
                    status: PaymentStatus::Unpaid,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    gatewayReference: $response->json('data.reference'),
                    payload: [
                        'paystack_reference' => $response->json('data.reference'),
                        'access_code' => $response->json('data.access_code'),
                        'return_url' => $session->redirectUrls->returnUrl,
                    ],
                );
            }

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $response->json('data.authorization_url'),
                gatewayReference: $response->json('data.reference'),
                payload: [
                    'paystack_reference' => $response->json('data.reference'),
                    'access_code' => $response->json('data.access_code'),
                ],
            );
        } catch (Throwable $e) {
            return new SessionResult(
                status: PaymentStatus::Failed,
                redirectUrl: $session->redirectUrls->failureUrl ?? '',
                failureReason: $e->getMessage(),
            );
        }
    }

    public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
    {
        if ($gatewayReference === null) {
            return new VerificationResult(status: $currentStatus);
        }

        try {
            $response = $this->client()->get('/transaction/verify/' . rawurlencode($gatewayReference));

            if ($response->failed()) {
                return new VerificationResult(status: $currentStatus);
            }

            /** @var array<string, mixed> $data */
            $data = $response->json('data') ?? [];

            $status = match ($data['status'] ?? null) {
                'success' => PaymentStatus::Paid,
                'failed' => PaymentStatus::Failed,
                'abandoned' => PaymentStatus::Canceled,
                default => $currentStatus,
            };

            [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($data);

            return new VerificationResult(
                status: $status,
                gatewayReference: $data['reference'] ?? $gatewayReference,
                paymentMethod: $paymentMethod,
                paymentMethodDetails: $paymentMethodDetails,
            );
        } catch (Throwable) {
            return new VerificationResult(status: $currentStatus);
        }
    }

    public function refund(RefundPayment $refund): RefundResult
    {
        if ($refund->gatewayReference === null) {
            return new RefundResult(
                status: RefundStatus::Failed,
                amount: $refund->amount,
                failureReason: __('No gateway reference found for this order.'),
            );
        }

        try {
            $refundPayload = [
                'transaction' => $refund->gatewayReference,
                'amount' => $this->toSubunit($refund->amount),
                'currency' => mb_strtoupper($refund->currencyCode),
            ];

            if ($refund->reason !== null) {
                $refundPayload['merchant_note'] = $refund->reason;
            }

            $response = $this->client()->post('/refund', $refundPayload);

            if ($response->failed()) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: $response->json('message', __('Paystack refund failed.')),
                );
            }

            $status = match ($response->json('data.status')) {
                'processed' => RefundStatus::Completed,
                'pending', 'processing' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            $refundId = $response->json('data.id');

            return new RefundResult(
                status: $status,
                amount: $refund->amount,
                gatewayReference: $refundId !== null ? (string) $refundId : null,
                payload: [
                    'refund_id' => $refundId,
                    'paystack_reference' => $refund->gatewayReference,
                ],
            );
        } catch (Throwable $e) {
            return new RefundResult(
                status: RefundStatus::Failed,
                amount: $refund->amount,
                failureReason: $e->getMessage(),
            );
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $secretKey = $this->gateway->credentials['secret_key'] ?? '';

        if (empty($secretKey)) {
            return false;
        }

        $signature = $request->header('x-paystack-signature', '');

        if (empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha512', $request->getContent(), $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true);

        $eventType = $payload['event'] ?? '';

        return match ($eventType) {
            'charge.success' => $this->parseChargeSucceeded($payload),
            'refund.processed' => $this->parseRefundProcessed($payload),
            default => new WebhookEvent(
                type: $eventType,
                payload: $payload,
            ),
        };
    }

    public function testConnection(): bool
    {
        try {
            return ! $this->client()->get('/transaction', ['perPage' => 1])->unauthorized();
        } catch (Throwable) {
            return false;
        }
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function isManual(): bool
    {
        return false;
    }

    private function toSubunit(string $amount): int
    {
        return BigDecimal::of($amount)
            ->multipliedBy(100)
            ->toScale(0, RoundingMode::HalfUp)
            ->toInt();
    }

    private function fromSubunit(int|string $amount): string
    {
        return BigDecimal::of($amount)
            ->dividedBy(100, 4, RoundingMode::HalfUp)
            ->toString();
    }

    private function isEmbeddedMode(): bool
    {
        $mode = $this->gateway->credentials['checkout_mode'] ?? CheckoutMode::Embedded->value;

        return $mode === CheckoutMode::Embedded->value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{string|null, array<string, mixed>|null}
     */
    private function extractPaymentMethodDetails(array $data): array
    {
        $channel = $data['channel'] ?? null;

        if (! is_string($channel)) {
            return [null, null];
        }

        $details = [];

        if ($channel === 'card' && isset($data['authorization']) && is_array($data['authorization'])) {
            $authorization = $data['authorization'];
            $details['brand'] = $authorization['brand'] ?? null;
            $details['last4'] = $authorization['last4'] ?? null;
        }

        return [$channel, $details !== [] ? $details : null];
    }

    private function client(): PendingRequest
    {
        $secretKey = $this->gateway->credentials['secret_key'] ?? '';

        if (empty($secretKey)) {
            throw new RuntimeException(__('Paystack secret key is not configured.'));
        }

        return Http::withToken($secretKey)
            ->baseUrl('https://api.paystack.co')
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asJson();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parseChargeSucceeded(array $payload): WebhookEvent
    {
        $data = $payload['data'] ?? [];
        $paymentSessionId = $data['metadata']['payment_session_id'] ?? null;

        [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($data);

        return new WebhookEvent(
            type: 'charge.success',
            status: PaymentStatus::Paid,
            paymentSessionId: $paymentSessionId,
            gatewayPaymentReference: $data['reference'] ?? null,
            payload: [
                'paystack_reference' => $data['reference'] ?? null,
                'paystack_transaction_id' => $data['id'] ?? null,
            ],
            paymentMethod: $paymentMethod,
            paymentMethodDetails: $paymentMethodDetails,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parseRefundProcessed(array $payload): WebhookEvent
    {
        $data = $payload['data'] ?? [];
        $transactionReference = $data['transaction_reference'] ?? null;
        $refundReference = $data['refund_reference'] ?? null;

        [$cumulativeRefunded, $status] = $this->resolveRefundState($transactionReference);

        return new WebhookEvent(
            type: 'refund.processed',
            status: $status,
            gatewayPaymentReference: $transactionReference,
            payload: [
                'paystack_reference' => $transactionReference,
                'refund_reference' => $refundReference,
                'amount' => isset($data['amount']) ? $this->fromSubunit($data['amount']) : null,
            ],
            cumulativeRefundTotal: $cumulativeRefunded,
            gatewayRefundReference: $refundReference !== null ? (string) $refundReference : null,
        );
    }

    /**
     * @return array{string|null, PaymentStatus}
     */
    private function resolveRefundState(?string $transactionReference): array
    {
        if ($transactionReference === null) {
            return [null, PaymentStatus::PartiallyRefunded];
        }

        try {
            $verifyResponse = $this->client()->get('/transaction/verify/' . rawurlencode($transactionReference));

            if ($verifyResponse->failed()) {
                return [null, PaymentStatus::PartiallyRefunded];
            }

            $transactionId = $verifyResponse->json('data.id');

            if ($transactionId === null) {
                return [null, PaymentStatus::PartiallyRefunded];
            }

            $refundsResponse = $this->client()->get('/refund', ['transaction' => $transactionId]);

            if ($refundsResponse->failed()) {
                return [null, PaymentStatus::PartiallyRefunded];
            }

            $total = BigDecimal::zero();

            foreach ($refundsResponse->json('data', []) as $refund) {
                if (($refund['status'] ?? null) === 'processed' && isset($refund['amount'])) {
                    $total = $total->plus(BigDecimal::of($refund['amount']));
                }
            }

            if ($total->isLessThanOrEqualTo(BigDecimal::zero())) {
                return [null, PaymentStatus::PartiallyRefunded];
            }

            $paidAmount = $verifyResponse->json('data.amount');

            $status = $paidAmount !== null && $total->isGreaterThanOrEqualTo(BigDecimal::of($paidAmount))
                ? PaymentStatus::Refunded
                : PaymentStatus::PartiallyRefunded;

            return [$this->fromSubunit($total->toString()), $status];
        } catch (Throwable) {
            return [null, PaymentStatus::PartiallyRefunded];
        }
    }
}
