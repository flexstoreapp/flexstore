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
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final readonly class StripeDriver implements PaymentDriver
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
            $paymentIntentId = $this->resolvePaymentIntentId($gatewayReference);

            if ($paymentIntentId === null) {
                return new VerificationResult(status: $currentStatus);
            }

            $response = $this->client()->get("/v1/payment_intents/{$paymentIntentId}", [
                'expand' => ['latest_charge.payment_method_details'],
            ]);

            if ($response->failed()) {
                return new VerificationResult(status: $currentStatus);
            }

            $paymentIntent = $response->json();

            $status = match ($paymentIntent['status'] ?? null) {
                'succeeded' => PaymentStatus::Paid,
                'canceled' => PaymentStatus::Canceled,
                'requires_payment_method' => isset($paymentIntent['last_payment_error'])
                    ? PaymentStatus::Failed
                    : $currentStatus,
                default => $currentStatus,
            };

            [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($paymentIntent);

            return new VerificationResult(
                status: $status,
                gatewayReference: $paymentIntentId,
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
            $headers = $refund->idempotencyKey !== null ? ['Idempotency-Key' => $refund->idempotencyKey] : [];

            $response = $this->client()->withHeaders($headers)->post('/v1/refunds', [
                'payment_intent' => $refund->gatewayReference,
                'amount' => CurrencyAmount::toSmallestUnit($refund->amount, $refund->currencyCode),
                'reason' => $this->mapRefundReason($refund->reason),
            ]);

            if ($response->failed()) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: $response->json('error.message', __('Stripe refund failed.')),
                );
            }

            $stripeRefund = $response->json();

            $status = match ($stripeRefund['status'] ?? null) {
                'succeeded' => RefundStatus::Completed,
                'pending', 'requires_action' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            return new RefundResult(
                status: $status,
                amount: CurrencyAmount::fromSmallestUnit($stripeRefund['amount'], $stripeRefund['currency']),
                gatewayReference: $stripeRefund['id'],
                payload: [
                    'refund_id' => $stripeRefund['id'],
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
        $payload = $request->getContent();
        $signatureHeader = $request->header('Stripe-Signature', '');
        $webhookSecret = $this->gateway->credentials['signing_secret'] ?? '';

        if (empty($signatureHeader) || empty($webhookSecret)) {
            return false;
        }

        $pairs = collect(explode(',', $signatureHeader))
            ->map(fn (string $part): array => explode('=', $part, 2) + [1 => '']);

        $timestamp = $pairs->firstWhere(fn (array $pair): bool => $pair[0] === 't')[1] ?? '';
        $signatures = $pairs->filter(fn (array $pair): bool => $pair[0] === 'v1')->pluck(1);

        if (empty($timestamp) || $signatures->isEmpty()) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $webhookSecret);

        return $signatures->contains(fn (string $sig): bool => hash_equals($expectedSignature, $sig));
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        /** @var array<string, mixed> $event */
        $event = json_decode($request->getContent(), true);

        $eventType = $event['type'] ?? '';
        $object = $event['data']['object'] ?? [];

        return match ($eventType) {
            'checkout.session.completed' => $this->parseCheckoutCompleted($object),
            'payment_intent.succeeded' => $this->parsePaymentSucceeded($object),
            'payment_intent.payment_failed' => $this->parsePaymentFailed($object),
            'charge.refunded' => $this->parseChargeRefunded($object),
            default => new WebhookEvent(
                type: $eventType,
                payload: $event['data'] ?? [],
            ),
        };
    }

    public function testConnection(): bool
    {
        try {
            return ! $this->client()->get('/v1/payment_intents', ['limit' => 1])->unauthorized();
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
            $totalInCents = CurrencyAmount::toSmallestUnit($session->amount, $session->currencyCode);

            $response = $this->client()->post('/v1/payment_intents', [
                'amount' => $totalInCents,
                'currency' => mb_strtolower($session->currencyCode),
                'automatic_payment_methods' => ['enabled' => 'true'],
                'description' => $session->description,
                'metadata' => $session->metadata,
                'receipt_email' => $session->customerEmail,
            ]);

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('error.message', __('Failed to create Stripe payment intent.')),
                );
            }

            $paymentIntent = $response->json();

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $session->redirectUrls->failureUrl ?? '',
                gatewayReference: $paymentIntent['id'],
                payload: [
                    'payment_intent_id' => $paymentIntent['id'],
                    'return_url' => $session->redirectUrls->returnUrl,
                    'client_secret' => $paymentIntent['client_secret'],
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
            $totalInCents = CurrencyAmount::toSmallestUnit($session->amount, $session->currencyCode);

            $response = $this->client()->post('/v1/checkout/sessions', [
                'mode' => 'payment',
                'customer_email' => $session->customerEmail,
                'line_items' => [[
                    'price_data' => [
                        'currency' => mb_strtolower($session->currencyCode),
                        'product_data' => [
                            'name' => $session->description,
                        ],
                        'unit_amount' => $totalInCents,
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $session->redirectUrls->returnUrl,
                'cancel_url' => $session->redirectUrls->cancelUrl,
                'metadata' => $session->metadata,
                'payment_intent_data' => [
                    'description' => $session->description,
                    'metadata' => $session->metadata,
                ],
            ]);

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('error.message', __('Failed to create Stripe checkout session.')),
                );
            }

            $stripeSession = $response->json();

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $stripeSession['url'] ?? $session->redirectUrls->failureUrl ?? '',
                gatewayReference: $stripeSession['id'],
                payload: [
                    'session_id' => $stripeSession['id'],
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

    private function resolvePaymentIntentId(string $gatewayReference): ?string
    {
        if (! str_starts_with($gatewayReference, 'cs_')) {
            return $gatewayReference;
        }

        $response = $this->client()->get("/v1/checkout/sessions/{$gatewayReference}");

        if ($response->failed()) {
            return null;
        }

        $paymentIntent = $response->json('payment_intent');

        return is_string($paymentIntent) ? $paymentIntent : null;
    }

    private function client(): PendingRequest
    {
        $secretKey = $this->gateway->credentials['secret_key'] ?? '';

        if (empty($secretKey)) {
            throw new RuntimeException(__('Stripe secret key is not configured.'));
        }

        return Http::withToken($secretKey)
            ->baseUrl('https://api.stripe.com')
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asForm();
    }

    private function mapRefundReason(?string $reason): string
    {
        return match ($reason) {
            'duplicate' => 'duplicate',
            'fraudulent' => 'fraudulent',
            default => 'requested_by_customer',
        };
    }

    /**
     * @param  array<string, mixed>  $paymentIntent
     * @return array{string|null, array<string, mixed>|null}
     */
    private function extractPaymentMethodDetails(array $paymentIntent): array
    {
        $charge = $paymentIntent['latest_charge'] ?? null;

        if (! is_array($charge)) {
            return [null, null];
        }

        $methodDetails = $charge['payment_method_details'] ?? null;

        if ($methodDetails === null) {
            return [null, null];
        }

        $type = $methodDetails['type'] ?? null;
        $details = [];

        if ($type === 'card' && isset($methodDetails['card'])) {
            $details['brand'] = $methodDetails['card']['brand'] ?? null;
            $details['last4'] = $methodDetails['card']['last4'] ?? null;
        }

        return [$type, $details !== [] ? $details : null];
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function parseCheckoutCompleted(array $session): WebhookEvent
    {
        $paymentIntentId = is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null;

        return new WebhookEvent(
            type: 'checkout.session.completed',
            status: PaymentStatus::Paid,
            paymentSessionId: $session['metadata']['payment_session_id'] ?? null,
            gatewayPaymentReference: $paymentIntentId,
            payload: [
                'session_id' => $session['id'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $paymentIntent
     */
    private function parsePaymentSucceeded(array $paymentIntent): WebhookEvent
    {
        [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($paymentIntent);

        return new WebhookEvent(
            type: 'payment_intent.succeeded',
            status: PaymentStatus::Paid,
            paymentSessionId: $paymentIntent['metadata']['payment_session_id'] ?? null,
            gatewayPaymentReference: $paymentIntent['id'] ?? null,
            payload: [
                'amount' => CurrencyAmount::fromSmallestUnit($paymentIntent['amount_received'] ?? 0, $paymentIntent['currency'] ?? 'USD'),
            ],
            paymentMethod: $paymentMethod,
            paymentMethodDetails: $paymentMethodDetails,
        );
    }

    /**
     * @param  array<string, mixed>  $paymentIntent
     */
    private function parsePaymentFailed(array $paymentIntent): WebhookEvent
    {
        return new WebhookEvent(
            type: 'payment_intent.payment_failed',
            status: PaymentStatus::Failed,
            paymentSessionId: $paymentIntent['metadata']['payment_session_id'] ?? null,
            gatewayPaymentReference: $paymentIntent['id'] ?? null,
            payload: [
                'error' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $charge
     */
    private function parseChargeRefunded(array $charge): WebhookEvent
    {
        $amountRefunded = CurrencyAmount::fromSmallestUnit($charge['amount_refunded'] ?? 0, $charge['currency'] ?? 'USD');

        $latestRefundId = null;
        $refunds = $charge['refunds']['data'] ?? [];

        if ($refunds !== []) {
            $latestRefundId = $refunds[0]['id'] ?? null;
        }

        $paymentIntent = $charge['payment_intent'] ?? null;

        return new WebhookEvent(
            type: 'charge.refunded',
            status: ($charge['refunded'] ?? false) ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
            gatewayPaymentReference: is_string($paymentIntent) ? $paymentIntent : null,
            payload: [
                'charge_id' => $charge['id'] ?? null,
                'amount_refunded' => $amountRefunded,
            ],
            cumulativeRefundTotal: $amountRefunded,
            gatewayRefundReference: $latestRefundId,
        );
    }
}
