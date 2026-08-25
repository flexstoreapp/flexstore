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

final readonly class MercadoPagoDriver implements PaymentDriver
{
    public function __construct(
        private PaymentGateway $gateway,
    ) {
    }

    public function createSession(CreateSession $session): SessionResult
    {
        try {
            $response = $this->client()->post('/checkout/preferences', $this->buildPreferenceData($session));

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('message', __('Failed to create Mercado Pago preference.')),
                );
            }

            $preferenceId = $response->json('id');
            $initPoint = $response->json('init_point');

            if ($preferenceId === null || $initPoint === null) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: __('Failed to create Mercado Pago preference.'),
                );
            }

            if ($this->isEmbeddedMode()) {
                return new SessionResult(
                    status: PaymentStatus::Unpaid,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    gatewayReference: $preferenceId,
                    payload: [
                        'mercadopago_preference_id' => $preferenceId,
                        'preference_id' => $preferenceId,
                        'return_url' => $session->redirectUrls->returnUrl,
                    ],
                );
            }

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $initPoint,
                gatewayReference: $preferenceId,
                payload: [
                    'mercadopago_preference_id' => $preferenceId,
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
            $paymentId = $this->resolvePaymentIdFromPreference($gatewayReference);

            if ($paymentId === null) {
                return new VerificationResult(status: $currentStatus);
            }

            $response = $this->client()->get("/v1/payments/{$paymentId}");

            if ($response->failed()) {
                return new VerificationResult(status: $currentStatus);
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($data);

            return new VerificationResult(
                status: $this->mapPaymentStatus($data['status'] ?? null, $currentStatus),
                gatewayReference: (string) ($data['id'] ?? $paymentId),
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
            $client = $this->client();

            if ($refund->idempotencyKey !== null) {
                $client = $client->withHeader('X-Idempotency-Key', $refund->idempotencyKey);
            }

            $response = $client->post("/v1/payments/{$refund->gatewayReference}/refunds", [
                'amount' => (float) CurrencyAmount::format($refund->amount, $refund->currencyCode),
            ]);

            if ($response->failed()) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: $response->json('message', __('Mercado Pago refund failed.')),
                );
            }

            $data = $response->json();

            $status = match ($data['status'] ?? null) {
                'approved' => RefundStatus::Completed,
                'in_process', 'pending' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            $refundId = $data['id'] ?? null;

            return new RefundResult(
                status: $status,
                amount: $refund->amount,
                gatewayReference: $refundId !== null ? (string) $refundId : null,
                payload: [
                    'refund_id' => $refundId,
                    'mercadopago_payment_id' => $refund->gatewayReference,
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

        $signatureHeader = $request->header('x-signature', '');

        if (empty($signatureHeader)) {
            return false;
        }

        [$ts, $signature] = $this->parseSignatureHeader($signatureHeader);

        if ($ts === null || $signature === null) {
            return false;
        }

        $resourceId = $this->resolveResourceId($request);

        if ($resourceId === null) {
            return false;
        }

        $requestId = $request->header('x-request-id', '');

        $manifest = "id:{$resourceId};request-id:{$requestId};ts:{$ts};";
        $expectedSignature = hash_hmac('sha256', $manifest, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?: [];

        $type = $payload['type'] ?? $request->query('type', '');

        if ($type !== 'payment') {
            return new WebhookEvent(
                type: is_string($type) ? $type : 'unknown',
                payload: $payload,
            );
        }

        $paymentId = $this->resolveResourceId($request);

        if ($paymentId === null) {
            return new WebhookEvent(type: 'payment.unknown');
        }

        try {
            $response = $this->client()->get("/v1/payments/{$paymentId}");

            if ($response->failed()) {
                return new WebhookEvent(type: 'payment.unknown');
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            return $this->buildPaymentEvent($data, $paymentId);
        } catch (Throwable) {
            return new WebhookEvent(type: 'payment.unknown');
        }
    }

    public function testConnection(): bool
    {
        try {
            return ! $this->client()->get('/v1/payment_methods')->unauthorized();
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
     * @param  array<string, mixed>  $data
     */
    private function buildPaymentEvent(array $data, string $paymentId): WebhookEvent
    {
        $paymentSessionId = $data['external_reference'] ?? $data['metadata']['payment_session_id'] ?? null;
        $amountRefunded = $data['transaction_amount_refunded'] ?? 0;
        $isRefund = BigDecimal::of((string) $amountRefunded)->isGreaterThan(BigDecimal::zero());

        [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($data);

        if ($isRefund) {
            return new WebhookEvent(
                type: 'payment.refunded',
                status: $this->resolveRefundStatus($data),
                paymentSessionId: $paymentSessionId !== null ? (string) $paymentSessionId : null,
                gatewayPaymentReference: $paymentId,
                payload: [
                    'mercadopago_payment_id' => $paymentId,
                    'amount_refunded' => (string) $amountRefunded,
                ],
                cumulativeRefundTotal: CurrencyAmount::format((string) $amountRefunded, (string) ($data['currency_id'] ?? 'USD')),
                gatewayRefundReference: $this->resolveLatestRefundId($data),
                paymentMethod: $paymentMethod,
                paymentMethodDetails: $paymentMethodDetails,
            );
        }

        return new WebhookEvent(
            type: 'payment.' . ($data['status'] ?? 'unknown'),
            status: $this->mapPaymentStatus($data['status'] ?? null, null),
            paymentSessionId: $paymentSessionId !== null ? (string) $paymentSessionId : null,
            gatewayPaymentReference: $paymentId,
            payload: [
                'mercadopago_payment_id' => $paymentId,
                'mercadopago_status' => $data['status'] ?? null,
            ],
            paymentMethod: $paymentMethod,
            paymentMethodDetails: $paymentMethodDetails,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveRefundStatus(array $data): PaymentStatus
    {
        $amountRefunded = BigDecimal::of((string) ($data['transaction_amount_refunded'] ?? '0'));
        $amountPaid = BigDecimal::of((string) ($data['transaction_amount'] ?? '0'));

        if ($amountRefunded->isLessThanOrEqualTo(BigDecimal::zero())) {
            return PaymentStatus::Paid;
        }

        if ($amountRefunded->isGreaterThanOrEqualTo($amountPaid)) {
            return PaymentStatus::Refunded;
        }

        return PaymentStatus::PartiallyRefunded;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveLatestRefundId(array $data): ?string
    {
        $refunds = $data['refunds'] ?? [];

        if (! is_array($refunds) || $refunds === []) {
            return null;
        }

        $latest = $refunds[array_key_last($refunds)];

        return isset($latest['id']) ? (string) $latest['id'] : null;
    }

    private function mapPaymentStatus(?string $status, ?PaymentStatus $default): PaymentStatus
    {
        return match ($status) {
            'approved' => PaymentStatus::Paid,
            'refunded' => PaymentStatus::Refunded,
            'rejected' => PaymentStatus::Failed,
            'cancelled' => PaymentStatus::Canceled,
            default => $default ?? PaymentStatus::Unpaid,
        };
    }

    private function resolvePaymentIdFromPreference(string $preferenceId): ?string
    {
        $response = $this->client()->get('/merchant_orders/search', ['preference_id' => $preferenceId]);

        if ($response->failed()) {
            return null;
        }

        $elements = $response->json('elements', []);

        if (! is_array($elements) || $elements === []) {
            return null;
        }

        $payments = $elements[0]['payments'] ?? [];

        if (! is_array($payments) || $payments === []) {
            return null;
        }

        $approved = null;
        $latest = null;

        foreach ($payments as $payment) {
            if (! isset($payment['id'])) {
                continue;
            }

            $latest = (string) $payment['id'];

            if (($payment['status'] ?? null) === 'approved') {
                $approved = (string) $payment['id'];
            }
        }

        return $approved ?? $latest;
    }

    /**
     * @return array{string|null, string|null}
     */
    private function parseSignatureHeader(string $header): array
    {
        $ts = null;
        $signature = null;

        foreach (explode(',', $header) as $part) {
            $segments = explode('=', mb_trim($part), 2);

            if (count($segments) !== 2) {
                continue;
            }

            [$key, $value] = $segments;
            $key = mb_trim($key);

            if ($key === 'ts') {
                $ts = mb_trim($value);
            } elseif ($key === 'v1') {
                $signature = mb_trim($value);
            }
        }

        return [$ts, $signature];
    }

    private function resolveResourceId(Request $request): ?string
    {
        $id = $request->query('data.id')
            ?? $request->query('data_id')
            ?? $request->query('id');

        if ($id === null) {
            /** @var array<string, mixed> $body */
            $body = json_decode($request->getContent(), true) ?: [];
            $id = $body['data']['id'] ?? null;
        }

        if ($id === null) {
            return null;
        }

        return mb_strtolower((string) $id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{string|null, array<string, mixed>|null}
     */
    private function extractPaymentMethodDetails(array $data): array
    {
        $paymentTypeId = $data['payment_type_id'] ?? null;

        if (! is_string($paymentTypeId)) {
            return [null, null];
        }

        $details = [];
        $card = $data['card'] ?? null;

        if (is_array($card) && $card !== []) {
            $details['brand'] = $data['payment_method_id'] ?? null;
            $details['last4'] = $card['last_four_digits'] ?? null;
        }

        return [$paymentTypeId, $details !== [] ? array_filter($details, fn ($value): bool => $value !== null) : null];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreferenceData(CreateSession $session): array
    {
        $data = [
            'items' => [
                [
                    'id' => $session->internalReference,
                    'title' => $session->description ?? $session->storeName ?? 'Order',
                    'quantity' => 1,
                    'currency_id' => mb_strtoupper($session->currencyCode),
                    'unit_price' => (float) CurrencyAmount::format($session->amount, $session->currencyCode),
                ],
            ],
            'back_urls' => array_filter([
                'success' => $session->redirectUrls->returnUrl,
                'pending' => $session->redirectUrls->returnUrl,
                'failure' => $session->redirectUrls->failureUrl,
            ]),
            'auto_return' => 'approved',
            'notification_url' => $session->callbackUrls->webhookUrl,
            'external_reference' => $session->internalReference,
            'metadata' => $session->metadata,
        ];

        if ($session->storeName !== null && $session->storeName !== '') {
            $data['statement_descriptor'] = mb_substr($session->storeName, 0, 22);
        }

        $payer = $this->buildPayer($session);

        if ($payer !== []) {
            $data['payer'] = $payer;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayer(CreateSession $session): array
    {
        $address = $session->billingAddress ?? $session->shippingAddress;

        $payer = array_filter([
            'email' => $session->customerEmail,
            'name' => $address['first_name'] ?? null,
            'surname' => $address['last_name'] ?? null,
        ]);

        if (isset($address['phone']) && $address['phone'] !== '') {
            $payer['phone'] = ['number' => (string) $address['phone']];
        }

        $streetAddress = array_filter([
            'zip_code' => $address['postal_code'] ?? null,
            'street_name' => $address['address_line_1'] ?? null,
        ]);

        if ($streetAddress !== []) {
            $payer['address'] = $streetAddress;
        }

        return $payer;
    }

    private function isEmbeddedMode(): bool
    {
        $mode = $this->gateway->credentials['checkout_mode'] ?? CheckoutMode::Embedded->value;

        return $mode === CheckoutMode::Embedded->value;
    }

    private function client(): PendingRequest
    {
        $accessToken = $this->gateway->credentials['access_token'] ?? '';

        if (empty($accessToken)) {
            throw new RuntimeException(__('Mercado Pago access token is not configured.'));
        }

        return Http::withToken($accessToken)
            ->baseUrl('https://api.mercadopago.com')
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asJson();
    }
}
