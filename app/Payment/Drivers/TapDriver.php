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

final readonly class TapDriver implements PaymentDriver
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {
    }

    public function createSession(CreateSession $session): SessionResult
    {
        $sourceId = 'src_all';

        if ($this->isEmbeddedMode()) {
            $cardToken = $session->providerOptions['card_token'] ?? null;

            if ($cardToken === null || $cardToken === '') {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: __('Card token is required for embedded checkout.'),
                );
            }

            $sourceId = $cardToken;
        }

        try {
            $response = $this->client()->post('/charges', $this->buildChargeData($session, $sourceId));

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $this->extractErrorMessage($response->json()),
                );
            }

            $data = $response->json();
            $chargeId = $data['id'] ?? null;
            $transactionUrl = $data['transaction']['url'] ?? null;

            if ($chargeId === null) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: __('Failed to create Tap charge.'),
                );
            }

            $status = $this->mapChargeStatus($data['status'] ?? null, PaymentStatus::Unpaid);

            if ($this->isEmbeddedMode()) {
                return new SessionResult(
                    status: $status,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    gatewayReference: $chargeId,
                    payload: [
                        'tap_charge_id' => $chargeId,
                        'return_url' => $transactionUrl ?? $session->redirectUrls->returnUrl,
                    ],
                );
            }

            if ($transactionUrl === null) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: __('Failed to create Tap charge.'),
                );
            }

            return new SessionResult(
                status: $status,
                redirectUrl: $transactionUrl,
                gatewayReference: $chargeId,
                payload: [
                    'tap_charge_id' => $chargeId,
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
            $response = $this->client()->get('/charges/' . rawurlencode($gatewayReference));

            if ($response->failed()) {
                return new VerificationResult(status: $currentStatus);
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($data);

            return new VerificationResult(
                status: $this->mapChargeStatus($data['status'] ?? null, $currentStatus),
                gatewayReference: $data['id'] ?? $gatewayReference,
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
                'charge_id' => $refund->gatewayReference,
                'amount' => (float) CurrencyAmount::format($refund->amount, $refund->currencyCode),
                'currency' => mb_strtoupper($refund->currencyCode),
                'reason' => $refund->reason ?? __('Requested by merchant'),
            ];

            $response = $this->client()->post('/refunds', $refundPayload);

            if ($response->failed()) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: $this->extractErrorMessage($response->json(), __('Tap refund failed.')),
                );
            }

            $data = $response->json();
            $refundId = $data['id'] ?? null;

            $status = match ($data['status'] ?? null) {
                'REFUNDED' => RefundStatus::Completed,
                'PENDING', 'IN_PROGRESS' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            return new RefundResult(
                status: $status,
                amount: $refund->amount,
                gatewayReference: $refundId !== null ? (string) $refundId : null,
                payload: [
                    'refund_id' => $refundId,
                    'tap_charge_id' => $refund->gatewayReference,
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

        $signature = $request->header('hashstring', '');

        if (empty($signature)) {
            return false;
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $expectedSignature = hash_hmac('sha256', $this->buildHashString($payload), $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $object = $payload['object'] ?? 'charge';

        if ($object === 'refund') {
            return $this->parseRefund($payload);
        }

        return $this->parseCharge($payload);
    }

    public function testConnection(): bool
    {
        try {
            return ! $this->client()->get('/charges/chg_connection_test')->unauthorized();
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

    /**
     * @return array<string, mixed>
     */
    private function buildChargeData(CreateSession $session, string $sourceId): array
    {
        return [
            'amount' => (float) CurrencyAmount::format($session->amount, $session->currencyCode),
            'currency' => mb_strtoupper($session->currencyCode),
            'threeDSecure' => true,
            'save_card' => false,
            'description' => $session->description,
            'metadata' => $session->metadata,
            'reference' => [
                'transaction' => $session->internalReference,
                'order' => $session->internalReference,
            ],
            'customer' => $this->buildCustomer($session),
            'source' => ['id' => $sourceId],
            'redirect' => ['url' => $session->redirectUrls->returnUrl],
            'post' => ['url' => $session->callbackUrls->webhookUrl],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCustomer(CreateSession $session): array
    {
        $address = $session->billingAddress ?? $session->shippingAddress ?? [];

        return array_filter([
            'first_name' => $address['first_name'] ?? __('Customer'),
            'last_name' => $address['last_name'] ?? null,
            'email' => $session->customerEmail,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function mapChargeStatus(?string $status, PaymentStatus $currentStatus): PaymentStatus
    {
        return match ($status) {
            'CAPTURED' => PaymentStatus::Paid,
            'DECLINED', 'FAILED', 'RESTRICTED', 'TIMEDOUT', 'UNKNOWN' => PaymentStatus::Failed,
            'CANCELLED', 'VOID', 'ABANDONED' => PaymentStatus::Canceled,
            default => $currentStatus,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{string|null, array<string, mixed>|null}
     */
    private function extractPaymentMethodDetails(array $data): array
    {
        $paymentMethod = $data['source']['payment_method'] ?? ($data['source']['type'] ?? null);

        if (! is_string($paymentMethod)) {
            $paymentMethod = null;
        }

        $card = $data['card'] ?? null;

        if (! is_array($card)) {
            return [$paymentMethod, null];
        }

        $details = array_filter([
            'brand' => $card['brand'] ?? null,
            'last4' => $card['last_four'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);

        return [$paymentMethod, $details !== [] ? $details : null];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parseCharge(array $payload): WebhookEvent
    {
        $paymentSessionId = $payload['metadata']['payment_session_id'] ?? null;

        [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($payload);

        return new WebhookEvent(
            type: 'charge.' . mb_strtolower((string) ($payload['status'] ?? 'unknown')),
            status: $this->mapChargeStatus($payload['status'] ?? null, PaymentStatus::Unpaid),
            paymentSessionId: is_string($paymentSessionId) ? $paymentSessionId : null,
            gatewayPaymentReference: $payload['id'] ?? null,
            payload: [
                'tap_charge_id' => $payload['id'] ?? null,
                'tap_status' => $payload['status'] ?? null,
            ],
            paymentMethod: $paymentMethod,
            paymentMethodDetails: $paymentMethodDetails,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parseRefund(array $payload): WebhookEvent
    {
        $chargeId = $payload['charge']['id'] ?? ($payload['charge_id'] ?? null);
        $refundId = $payload['id'] ?? null;

        [$cumulativeRefunded, $status] = $this->resolveRefundState(
            is_string($chargeId) ? $chargeId : null,
            $payload['amount'] ?? null,
            $payload['currency'] ?? null,
        );

        return new WebhookEvent(
            type: 'refund.' . mb_strtolower((string) ($payload['status'] ?? 'unknown')),
            status: $status,
            gatewayPaymentReference: is_string($chargeId) ? $chargeId : null,
            payload: [
                'tap_charge_id' => $chargeId,
                'refund_id' => $refundId,
                'amount' => $payload['amount'] ?? null,
            ],
            cumulativeRefundTotal: $cumulativeRefunded,
            gatewayRefundReference: $refundId !== null ? (string) $refundId : null,
        );
    }

    /**
     * @return array{string|null, PaymentStatus}
     */
    private function resolveRefundState(?string $chargeId, mixed $refundAmount, mixed $currency): array
    {
        if ($chargeId === null || $refundAmount === null || ! is_string($currency)) {
            return [null, PaymentStatus::PartiallyRefunded];
        }

        try {
            $response = $this->client()->get('/charges/' . rawurlencode($chargeId));

            if ($response->failed()) {
                return [CurrencyAmount::format((string) $refundAmount, $currency), PaymentStatus::PartiallyRefunded];
            }

            $chargeAmount = $response->json('amount');
            $refunded = BigDecimal::of((string) $refundAmount);

            if ($chargeAmount === null) {
                return [CurrencyAmount::format($refunded->__toString(), $currency), PaymentStatus::PartiallyRefunded];
            }

            $status = $refunded->isGreaterThanOrEqualTo(BigDecimal::of((string) $chargeAmount))
                ? PaymentStatus::Refunded
                : PaymentStatus::PartiallyRefunded;

            return [CurrencyAmount::format($refunded->__toString(), $currency), $status];
        } catch (Throwable) {
            return [null, PaymentStatus::PartiallyRefunded];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildHashString(array $payload): string
    {
        $currency = is_string($payload['currency'] ?? null) ? $payload['currency'] : '';
        $amount = isset($payload['amount']) && $currency !== ''
            ? CurrencyAmount::format((string) $payload['amount'], $currency)
            : (string) ($payload['amount'] ?? '');

        return implode('', [
            'x_id' . ($payload['id'] ?? ''),
            'x_amount' . $amount,
            'x_currency' . $currency,
            'x_gateway_reference' . ($payload['reference']['gateway'] ?? ''),
            'x_payment_reference' . ($payload['reference']['payment'] ?? ''),
            'x_status' . ($payload['status'] ?? ''),
            'x_created' . ($payload['transaction']['created'] ?? ''),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function extractErrorMessage(?array $body, ?string $default = null): string
    {
        $errors = $body['errors'] ?? null;

        if (is_array($errors) && isset($errors[0]['description']) && is_string($errors[0]['description'])) {
            return $errors[0]['description'];
        }

        return $default ?? __('Failed to initialize Tap transaction.');
    }

    private function client(): PendingRequest
    {
        $secretKey = $this->gateway->credentials['secret_key'] ?? '';

        if (empty($secretKey)) {
            throw new RuntimeException(__('Tap secret key is not configured.'));
        }

        return Http::withToken($secretKey)
            ->baseUrl('https://api.tap.company/v2')
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asJson();
    }
}
