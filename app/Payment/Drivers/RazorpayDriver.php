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
use App\Utilities\CurrencyAmount;
use Brick\Math\BigDecimal;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final readonly class RazorpayDriver implements PaymentDriver
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {
    }

    public function createSession(CreateSession $session): SessionResult
    {
        return $this->isEmbeddedMode()
            ? $this->createEmbeddedSession($session)
            : $this->createHostedSession($session);
    }

    public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
    {
        if ($gatewayReference === null) {
            return new VerificationResult(status: $currentStatus);
        }

        try {
            if (str_starts_with($gatewayReference, 'plink_')) {
                return $this->verifyPaymentLink($gatewayReference, $currentStatus);
            }

            return $this->verifyRazorpayOrder($gatewayReference, $currentStatus);
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
            $paymentId = $this->resolvePaymentId($refund->gatewayReference);

            if ($paymentId === null) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: __('No payment found for this order.'),
                );
            }

            $refundAmount = BigDecimal::of($refund->amount);

            $notes = [];

            if ($refund->orderId !== null) {
                $notes['order_id'] = $refund->orderId;
            }

            if ($refund->reason !== null) {
                $notes['reason'] = $refund->reason;
            }

            $refundPayload = [
                'amount' => CurrencyAmount::toSmallestUnit($refundAmount->toString(), $refund->currencyCode),
                'notes' => $notes,
            ];

            if ($refund->idempotencyKey !== null) {
                $refundPayload['receipt'] = $refund->idempotencyKey;
            }

            $response = $this->client()->post("/payments/{$paymentId}/refund", $refundPayload);

            if ($response->failed()) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: $response->json('error.description', __('Razorpay refund failed.')),
                );
            }

            $data = $response->json();
            $refundStatus = $data['status'] ?? null;

            $status = match ($refundStatus) {
                'processed' => RefundStatus::Completed,
                'pending' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            return new RefundResult(
                status: $status,
                amount: $refund->amount,
                gatewayReference: $data['id'] ?? null,
                payload: [
                    'refund_id' => $data['id'] ?? null,
                    'razorpay_payment_id' => $paymentId,
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
        $webhookSecret = $this->gateway->credentials['webhook_secret'] ?? '';

        if (empty($webhookSecret)) {
            return false;
        }

        $signature = $request->header('X-Razorpay-Signature', '');

        if (empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true);

        $eventType = $payload['event'] ?? '';

        return match ($eventType) {
            'payment.authorized', 'payment.captured' => $this->parsePaymentSucceeded($eventType, $payload),
            'payment.failed' => $this->parsePaymentFailed($payload),
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
            return ! $this->client()->get('/payments', ['count' => 1])->unauthorized();
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

    private function isEmbeddedMode(): bool
    {
        $mode = $this->gateway->credentials['checkout_mode'] ?? CheckoutMode::Embedded->value;

        return $mode === CheckoutMode::Embedded->value;
    }

    private function createEmbeddedSession(CreateSession $session): SessionResult
    {
        try {
            $currency = mb_strtoupper($session->currencyCode);
            $amountInPaise = CurrencyAmount::toSmallestUnit($session->amount, $session->currencyCode);

            $response = $this->client()->post('/orders', [
                'amount' => $amountInPaise,
                'currency' => $currency,
                'receipt' => $session->internalReference,
                'payment_capture' => 1,
                'notes' => ['payment_session_id' => $session->internalReference],
            ]);

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('error.description', __('Failed to create Razorpay order.')),
                );
            }

            $data = $response->json();

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $session->redirectUrls->failureUrl ?? '',
                gatewayReference: $data['id'],
                payload: [
                    'razorpay_order_id' => $data['id'],
                    'key_id' => $this->gateway->credentials['key_id'] ?? null,
                    'return_url' => $session->redirectUrls->returnUrl,
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

    private function createHostedSession(CreateSession $session): SessionResult
    {
        try {
            $currency = mb_strtoupper($session->currencyCode);
            $amountInPaise = CurrencyAmount::toSmallestUnit($session->amount, $session->currencyCode);

            $linkResponse = $this->client()->post('/payment_links', [
                'amount' => $amountInPaise,
                'currency' => $currency,
                'description' => $session->description,
                'callback_url' => $session->redirectUrls->returnUrl,
                'callback_method' => 'get',
                'notes' => ['payment_session_id' => $session->internalReference],
            ]);

            if ($linkResponse->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $linkResponse->json('error.description', __('Failed to create Razorpay payment link.')),
                );
            }

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $linkResponse->json('short_url'),
                gatewayReference: $linkResponse->json('id'),
                payload: [
                    'payment_link_id' => $linkResponse->json('id'),
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

    private function verifyPaymentLink(string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
    {
        $response = $this->client()->get("/payment_links/{$gatewayReference}");

        if ($response->failed()) {
            return new VerificationResult(status: $currentStatus);
        }

        $status = $response->json('status');

        if ($status === 'paid') {
            $paymentId = $response->json('payments.0.payment_id');
            $paymentMethod = $response->json('payments.0.method');

            return new VerificationResult(
                status: PaymentStatus::Paid,
                gatewayReference: $paymentId ?? $gatewayReference,
                paymentMethod: $paymentMethod,
            );
        }

        $paymentStatus = match ($status) {
            'cancelled' => PaymentStatus::Canceled,
            'expired' => PaymentStatus::Failed,
            default => null,
        };

        if ($paymentStatus !== null) {
            return new VerificationResult(
                status: $paymentStatus,
                gatewayReference: $gatewayReference,
            );
        }

        return new VerificationResult(status: $currentStatus);
    }

    private function verifyRazorpayOrder(string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
    {
        $response = $this->client()->get("/orders/{$gatewayReference}");

        if ($response->failed()) {
            return new VerificationResult(status: $currentStatus);
        }

        $orderStatus = $response->json('status');

        if ($orderStatus === 'paid' || $orderStatus === 'attempted') {
            $paymentsResponse = $this->client()->get("/orders/{$gatewayReference}/payments");
            $payments = $paymentsResponse->json('items', []);
            $lastFailedPaymentId = null;

            foreach ($payments as $payment) {
                if (in_array($payment['status'] ?? null, ['captured', 'authorized'])) {
                    [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($payment);

                    return new VerificationResult(
                        status: PaymentStatus::Paid,
                        gatewayReference: $payment['id'] ?? $gatewayReference,
                        paymentMethod: $paymentMethod,
                        paymentMethodDetails: $paymentMethodDetails,
                    );
                }

                if (($payment['status'] ?? null) === 'failed') {
                    $lastFailedPaymentId = $payment['id'] ?? $gatewayReference;
                }
            }

            if ($lastFailedPaymentId !== null) {
                return new VerificationResult(
                    status: PaymentStatus::Failed,
                    gatewayReference: $lastFailedPaymentId,
                );
            }
        }

        return new VerificationResult(status: $currentStatus);
    }

    /**
     * Resolves the Razorpay payment ID from a gateway reference which may be
     * a payment ID (pay_), payment link ID (plink_), or order ID (order_).
     */
    private function resolvePaymentId(string $gatewayReference): ?string
    {
        if (str_starts_with($gatewayReference, 'pay_')) {
            return $gatewayReference;
        }

        if (str_starts_with($gatewayReference, 'plink_')) {
            $response = $this->client()->get("/payment_links/{$gatewayReference}");

            return $response->successful() ? $response->json('payments.0.payment_id') : null;
        }

        $response = $this->client()->get("/orders/{$gatewayReference}/payments");

        return $response->successful() ? $response->json('items.0.id') : null;
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return array{string|null, array<string, mixed>|null}
     */
    private function extractPaymentMethodDetails(array $payment): array
    {
        $method = $payment['method'] ?? null;

        if (! is_string($method)) {
            return [null, null];
        }

        $details = [];

        if ($method === 'card' && isset($payment['card'])) {
            $card = $payment['card'];
            $details['brand'] = $card['network'] ?? null;
            $details['last4'] = $card['last4'] ?? null;
        }

        return [$method, $details !== [] ? $details : null];
    }

    private function client(): PendingRequest
    {
        $keyId = $this->gateway->credentials['key_id'] ?? '';
        $keySecret = $this->gateway->credentials['key_secret'] ?? '';

        if (empty($keyId) || empty($keySecret)) {
            throw new RuntimeException(__('Razorpay key ID and key secret are not configured.'));
        }

        return Http::withBasicAuth($keyId, $keySecret)
            ->baseUrl('https://api.razorpay.com/v1')
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asJson();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parsePaymentSucceeded(string $eventType, array $payload): WebhookEvent
    {
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $paymentSessionId = $payment['notes']['payment_session_id'] ?? null;

        [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($payment);

        return new WebhookEvent(
            type: $eventType,
            status: PaymentStatus::Paid,
            paymentSessionId: $paymentSessionId,
            gatewayPaymentReference: $payment['id'] ?? null,
            gatewayOrderReference: $payment['order_id'] ?? null,
            payload: [
                'razorpay_payment_id' => $payment['id'] ?? null,
            ],
            paymentMethod: $paymentMethod,
            paymentMethodDetails: $paymentMethodDetails,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parsePaymentFailed(array $payload): WebhookEvent
    {
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $paymentSessionId = $payment['notes']['payment_session_id'] ?? null;

        return new WebhookEvent(
            type: 'payment.failed',
            status: PaymentStatus::Failed,
            paymentSessionId: $paymentSessionId,
            gatewayPaymentReference: $payment['id'] ?? null,
            payload: [
                'razorpay_payment_id' => $payment['id'] ?? null,
                'error_description' => $payment['error_description'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parseRefundProcessed(array $payload): WebhookEvent
    {
        $refund = $payload['payload']['refund']['entity'] ?? [];
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $paymentId = $refund['payment_id'] ?? null;
        $paymentSessionId = $refund['notes']['payment_session_id']
            ?? $payment['notes']['payment_session_id']
            ?? null;

        $status = ($payment['refund_status'] ?? null) === 'partial'
            ? PaymentStatus::PartiallyRefunded
            : PaymentStatus::Refunded;

        $currency = $payment['currency'] ?? 'INR';
        $cumulativeRefunded = isset($payment['amount_refunded']) && $payment['amount_refunded'] > 0
            ? CurrencyAmount::fromSmallestUnit($payment['amount_refunded'], $currency)
            : $this->fetchCumulativeRefundAmount($paymentId);

        return new WebhookEvent(
            type: 'refund.processed',
            status: $status,
            paymentSessionId: $paymentSessionId,
            gatewayPaymentReference: $paymentId,
            gatewayOrderReference: $payment['order_id'] ?? null,
            payload: [
                'refund_id' => $refund['id'] ?? null,
                'razorpay_payment_id' => $paymentId,
                'amount' => isset($refund['amount']) ? CurrencyAmount::fromSmallestUnit($refund['amount'], $currency) : null,
            ],
            cumulativeRefundTotal: $cumulativeRefunded,
            gatewayRefundReference: $refund['id'] ?? null,
        );
    }

    private function fetchCumulativeRefundAmount(?string $paymentId): ?string
    {
        if ($paymentId === null) {
            return null;
        }

        try {
            $response = $this->client()->get("/payments/{$paymentId}");

            if ($response->failed()) {
                return null;
            }

            $amountRefunded = $response->json('amount_refunded');

            if ($amountRefunded === null || $amountRefunded === 0) {
                return null;
            }

            $currency = $response->json('currency') ?? 'INR';

            return CurrencyAmount::fromSmallestUnit($amountRefunded, $currency);
        } catch (Throwable) {
            return null;
        }
    }
}
