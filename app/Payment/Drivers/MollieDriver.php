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

final readonly class MollieDriver implements PaymentDriver
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {
    }

    public function createSession(CreateSession $session): SessionResult
    {
        if (! $this->isEmbeddedMode()) {
            return $this->createHostedSession($session);
        }

        $cardToken = $session->providerOptions['card_token'] ?? null;

        if ($cardToken === null || $cardToken === '') {
            return new SessionResult(
                status: PaymentStatus::Failed,
                redirectUrl: $session->redirectUrls->failureUrl ?? '',
                failureReason: __('Card token is required for embedded checkout.'),
            );
        }

        return $this->createEmbeddedSession($session, $cardToken);
    }

    public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
    {
        if ($gatewayReference === null) {
            return new VerificationResult(status: $currentStatus);
        }

        try {
            $response = $this->client()->get("/payments/{$gatewayReference}");

            if ($response->failed()) {
                return new VerificationResult(status: $currentStatus);
            }

            $status = $response->json('status');

            $paymentStatus = match ($status) {
                'paid' => PaymentStatus::Paid,
                'failed', 'expired' => PaymentStatus::Failed,
                'canceled' => PaymentStatus::Canceled,
                default => $currentStatus,
            };

            [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($response->json());

            return new VerificationResult(
                status: $paymentStatus,
                gatewayReference: $response->json('id'),
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
                'amount' => [
                    'currency' => mb_strtoupper($refund->currencyCode),
                    'value' => CurrencyAmount::format($refund->amount, $refund->currencyCode),
                ],
            ];

            if ($refund->reason !== null) {
                $refundPayload['description'] = $refund->reason;
            }

            $client = $this->client();

            if ($refund->idempotencyKey !== null) {
                $client = $client->withHeader('Idempotency-Key', $refund->idempotencyKey);
            }

            $response = $client->post("/payments/{$refund->gatewayReference}/refunds", $refundPayload);

            if ($response->failed()) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: $response->json('detail', __('Mollie refund failed.')),
                );
            }

            $data = $response->json();
            $refundStatus = $data['status'] ?? null;

            $status = match ($refundStatus) {
                'refunded' => RefundStatus::Completed,
                'pending', 'processing', 'queued' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            return new RefundResult(
                status: $status,
                amount: $refund->amount,
                gatewayReference: $data['id'] ?? null,
                payload: [
                    'refund_id' => $data['id'] ?? null,
                    'mollie_payment_id' => $refund->gatewayReference,
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
        $paymentId = $request->input('id');

        return is_string($paymentId) && preg_match('/^tr_[a-zA-Z0-9]+$/', $paymentId) === 1;
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $paymentId = $request->input('id', '');

        try {
            $response = $this->client()->get("/payments/{$paymentId}", ['embed' => 'refunds']);

            if ($response->failed()) {
                return new WebhookEvent(
                    type: 'payment.unknown',
                );
            }

            $data = $response->json();
            $mollieStatus = $data['status'] ?? '';
            $paymentSessionId = $data['metadata']['payment_session_id'] ?? null;
            $hasRefundInfo = array_key_exists('amountRefunded', $data);
            $amountRefunded = $data['amountRefunded']['value'] ?? null;

            $status = match (true) {
                $hasRefundInfo && $mollieStatus === 'paid' => $this->resolveRefundStatus($data),
                $mollieStatus === 'paid' => PaymentStatus::Paid,
                in_array($mollieStatus, ['expired', 'failed'], true) => PaymentStatus::Failed,
                $mollieStatus === 'canceled' => PaymentStatus::Canceled,
                default => null,
            };

            $type = $hasRefundInfo && $mollieStatus === 'paid'
                ? 'payment.refunded'
                : "payment.{$mollieStatus}";

            $refundAmount = $hasRefundInfo && $amountRefunded !== null && BigDecimal::of($amountRefunded)->isGreaterThan(BigDecimal::zero())
                ? $amountRefunded
                : null;

            $latestRefundId = null;
            $embeddedRefunds = $data['_embedded']['refunds'] ?? [];

            if ($refundAmount !== null && $embeddedRefunds !== []) {
                $latestRefundId = $embeddedRefunds[array_key_last($embeddedRefunds)]['id'] ?? null;
            }

            [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($data);

            return new WebhookEvent(
                type: $type,
                status: $status,
                paymentSessionId: $paymentSessionId,
                gatewayPaymentReference: $paymentId,
                payload: [
                    'mollie_payment_id' => $paymentId,
                    'mollie_status' => $mollieStatus,
                    'amount_refunded' => $amountRefunded,
                ],
                cumulativeRefundTotal: $refundAmount,
                gatewayRefundReference: $latestRefundId,
                paymentMethod: $paymentMethod,
                paymentMethodDetails: $paymentMethodDetails,
            );
        } catch (Throwable) {
            return new WebhookEvent(
                type: 'payment.unknown',
            );
        }
    }

    public function testConnection(): bool
    {
        try {
            return ! $this->client()->get('/methods')->unauthorized();
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

    /**
     * @param  array<string, mixed>  $paymentData
     */
    private function resolveRefundStatus(array $paymentData): PaymentStatus
    {
        $amountRefunded = BigDecimal::of($paymentData['amountRefunded']['value'] ?? '0');
        $amountPaid = BigDecimal::of($paymentData['amount']['value'] ?? '0');

        if ($amountRefunded->isLessThanOrEqualTo(BigDecimal::zero())) {
            return PaymentStatus::Paid;
        }

        if ($amountRefunded->isGreaterThanOrEqualTo($amountPaid)) {
            return PaymentStatus::Refunded;
        }

        return PaymentStatus::PartiallyRefunded;
    }

    /**
     * @param  array<string, mixed>  $paymentData
     * @return array{string|null, array<string, mixed>|null}
     */
    private function extractPaymentMethodDetails(array $paymentData): array
    {
        $method = $paymentData['method'] ?? null;

        if (! is_string($method)) {
            return [null, null];
        }

        $rawDetails = $paymentData['details'] ?? null;

        if (! is_array($rawDetails)) {
            return [$method, null];
        }

        $details = [];

        if (isset($rawDetails['cardHolder'])) {
            $details['brand'] = $rawDetails['cardLabel'] ?? null;
            $details['last4'] = isset($rawDetails['cardNumber'])
                ? mb_substr((string) $rawDetails['cardNumber'], -4)
                : null;
        }

        return [$method, $details !== [] ? $details : null];
    }

    private function isEmbeddedMode(): bool
    {
        $mode = $this->gateway->credentials['checkout_mode'] ?? CheckoutMode::Embedded->value;

        return $mode === CheckoutMode::Embedded->value;
    }

    private function createEmbeddedSession(CreateSession $session, string $cardToken): SessionResult
    {
        try {
            $profileId = $this->gateway->credentials['profile_id'] ?? null;
            $paymentData = $this->buildPaymentData($session);
            $paymentData['cardToken'] = $cardToken;
            $returnUrl = $paymentData['redirectUrl'];

            $response = $this->client()->post('/payments', $paymentData);

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('detail', __('Failed to create Mollie payment.')),
                );
            }

            $data = $response->json();
            $checkoutHref = $data['_links']['checkout']['href'] ?? null;

            return new SessionResult(
                status: $data['status'] === 'paid' ? PaymentStatus::Paid : PaymentStatus::Unpaid,
                redirectUrl: $session->redirectUrls->failureUrl ?? '',
                gatewayReference: $data['id'],
                payload: [
                    'mollie_payment_id' => $data['id'],
                    'profile_id' => $profileId,
                    'return_url' => $checkoutHref ?? $returnUrl,
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
            $response = $this->client()->post('/payments', $this->buildPaymentData($session));

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('detail', __('Failed to create Mollie payment.')),
                );
            }

            $data = $response->json();
            $checkoutUrl = $data['_links']['checkout']['href'] ?? null;

            if ($checkoutUrl === null) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: __('Failed to create Mollie payment.'),
                );
            }

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $checkoutUrl,
                gatewayReference: $data['id'],
                payload: [
                    'mollie_payment_id' => $data['id'],
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

    /**
     * @return array<string, mixed>
     */
    private function buildPaymentData(CreateSession $session): array
    {
        $data = [
            'amount' => [
                'currency' => mb_strtoupper($session->currencyCode),
                'value' => CurrencyAmount::format($session->amount, $session->currencyCode),
            ],
            'description' => $session->description,
            'redirectUrl' => $session->redirectUrls->returnUrl,
            'cancelUrl' => $session->redirectUrls->cancelUrl,
            'webhookUrl' => $session->callbackUrls->webhookUrl,
            'metadata' => $session->metadata,
        ];

        if ($session->shippingAddress !== null) {
            $data['shippingAddress'] = $this->formatAddress($session->shippingAddress, $session->customerEmail);
        }

        if ($session->billingAddress !== null) {
            $data['billingAddress'] = $this->formatAddress($session->billingAddress, $session->customerEmail);
        }

        return $data;
    }

    /**
     * @param  array<string, string|null>  $address
     * @return array<string, string>
     */
    private function formatAddress(array $address, ?string $email = null): array
    {
        return array_filter([
            'givenName' => $address['first_name'] ?? null,
            'familyName' => $address['last_name'] ?? null,
            'streetAndNumber' => $address['address_line_1'] ?? null,
            'streetAdditional' => $address['address_line_2'] ?? null,
            'postalCode' => $address['postal_code'] ?? null,
            'city' => $address['city'] ?? null,
            'region' => $address['state'] ?? null,
            'country' => $address['country_code'] ?? null,
            'email' => $email,
            'phone' => $address['phone'] ?? null,
        ]);
    }

    private function client(): PendingRequest
    {
        $apiKey = $this->gateway->credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            throw new RuntimeException(__('Mollie API key is not configured.'));
        }

        return Http::baseUrl('https://api.mollie.com/v2')
            ->withToken($apiKey)
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asJson();
    }
}
