<?php

declare(strict_types=1);

namespace App\Payment\Drivers;

use App\DTOs\Address;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final readonly class PaypalDriver implements PaymentDriver
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
            $response = $this->client()->get("/v2/checkout/orders/{$gatewayReference}");

            if ($response->failed()) {
                return new VerificationResult(status: $currentStatus);
            }

            $orderData = $response->json();
            $status = $orderData['status'] ?? null;

            if ($status === 'VOIDED') {
                return new VerificationResult(
                    status: PaymentStatus::Canceled,
                    gatewayReference: $gatewayReference,
                );
            }

            if ($status === 'APPROVED') {
                $captureResponse = $this->client()
                    ->send('POST', "/v2/checkout/orders/{$gatewayReference}/capture");

                if ($captureResponse->successful()) {
                    $captureData = $captureResponse->json();
                    $captureId = $this->extractCaptureId($captureData);
                    [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($captureData);

                    return new VerificationResult(
                        status: PaymentStatus::Paid,
                        gatewayReference: $captureId ?? $gatewayReference,
                        paymentMethod: $paymentMethod,
                        paymentMethodDetails: $paymentMethodDetails,
                    );
                }

                return new VerificationResult(status: $currentStatus);
            }

            if ($status === 'COMPLETED') {
                $captureId = $this->extractCaptureId($orderData);
                [$paymentMethod, $paymentMethodDetails] = $this->extractPaymentMethodDetails($orderData);

                return new VerificationResult(
                    status: PaymentStatus::Paid,
                    gatewayReference: $captureId ?? $gatewayReference,
                    paymentMethod: $paymentMethod,
                    paymentMethodDetails: $paymentMethodDetails,
                );
            }

            return new VerificationResult(status: $currentStatus);
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
            $payload = [
                'amount' => [
                    'value' => CurrencyAmount::format($refund->amount, $refund->currencyCode),
                    'currency_code' => mb_strtoupper($refund->currencyCode),
                ],
            ];

            if ($refund->reason !== null) {
                $payload['note_to_payer'] = $refund->reason;
            }

            $client = $this->client();

            if ($refund->idempotencyKey !== null) {
                $client = $client->withHeader('PayPal-Request-Id', $refund->idempotencyKey);
            }

            $response = $client->post("/v2/payments/captures/{$refund->gatewayReference}/refund", $payload);

            if ($response->failed()) {
                return new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $refund->amount,
                    failureReason: $response->json('message', __('PayPal refund failed.')),
                );
            }

            $data = $response->json();
            $refundStatus = $data['status'] ?? null;

            $status = match ($refundStatus) {
                'COMPLETED' => RefundStatus::Completed,
                'PENDING' => RefundStatus::Pending,
                default => RefundStatus::Failed,
            };

            return new RefundResult(
                status: $status,
                amount: $data['amount']['value'] ?? $refund->amount,
                gatewayReference: $data['id'] ?? null,
                payload: [
                    'refund_id' => $data['id'] ?? null,
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
        $webhookId = $this->gateway->credentials['webhook_id'] ?? '';

        if (empty($webhookId)) {
            return false;
        }

        try {
            $response = $this->client()->post('/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO', ''),
                'cert_url' => $request->header('PAYPAL-CERT-URL', ''),
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID', ''),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG', ''),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME', ''),
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($request->getContent(), true),
            ]);

            return $response->successful() && $response->json('verification_status') === 'SUCCESS';
        } catch (Throwable) {
            return false;
        }
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true);

        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        return match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->parseCaptureCompleted($resource),
            'PAYMENT.CAPTURE.DENIED' => $this->parseCaptureDenied($resource),
            'PAYMENT.CAPTURE.REFUNDED' => $this->parseCaptureRefunded($resource),
            default => new WebhookEvent(
                type: $eventType,
                payload: $payload,
            ),
        };
    }

    public function testConnection(): bool
    {
        try {
            [$clientId, $clientSecret] = $this->credentials();

            return Http::baseUrl($this->baseUrl())
                ->withBasicAuth($clientId, $clientSecret)
                ->connectTimeout(5)
                ->timeout(15)
                ->asForm()
                ->post('/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->unauthorized() === false;
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

            $purchaseUnit = [
                'reference_id' => $session->internalReference,
                'custom_id' => $session->internalReference,
                'description' => $session->description,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => CurrencyAmount::format($session->amount, $session->currencyCode),
                ],
            ];

            $shipping = $this->buildShipping($session->shippingAddress);

            if ($shipping !== null) {
                $purchaseUnit['shipping'] = $shipping;
            }

            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [$purchaseUnit],
            ];

            $payer = $this->buildPayer($session->billingAddress, $session->customerEmail);

            if ($payer !== null) {
                $payload['payer'] = $payer;
            }

            $response = $this->client()->post('/v2/checkout/orders', $payload);

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('message', __('Failed to create PayPal order.')),
                );
            }

            $data = $response->json();

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $session->redirectUrls->failureUrl ?? '',
                gatewayReference: $data['id'],
                payload: [
                    'paypal_order_id' => $data['id'],
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

            $purchaseUnit = [
                'reference_id' => $session->internalReference,
                'custom_id' => $session->internalReference,
                'description' => $session->description,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => CurrencyAmount::format($session->amount, $session->currencyCode),
                ],
            ];

            $shipping = $this->buildShipping($session->shippingAddress);

            $experienceContext = [
                'return_url' => $session->redirectUrls->returnUrl,
                'cancel_url' => $session->redirectUrls->cancelUrl,
                'user_action' => 'PAY_NOW',
                'brand_name' => (string) $session->storeName,
            ];

            if ($shipping !== null) {
                $purchaseUnit['shipping'] = $shipping;
                $experienceContext['shipping_preference'] = 'SET_PROVIDED_ADDRESS';
            }

            $paypalSource = ['experience_context' => $experienceContext];

            $payer = $this->buildPayer($session->billingAddress, $session->customerEmail);

            if ($payer !== null) {
                $paypalSource += $payer;
            }

            $response = $this->client()->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [$purchaseUnit],
                'payment_source' => [
                    'paypal' => $paypalSource,
                ],
            ]);

            if ($response->failed()) {
                return new SessionResult(
                    status: PaymentStatus::Failed,
                    redirectUrl: $session->redirectUrls->failureUrl ?? '',
                    failureReason: $response->json('message', __('Failed to create PayPal order.')),
                );
            }

            $data = $response->json();
            $approvalUrl = $this->extractApprovalUrl($data);

            return new SessionResult(
                status: PaymentStatus::Unpaid,
                redirectUrl: $approvalUrl ?? $session->redirectUrls->failureUrl ?? '',
                gatewayReference: $data['id'],
                payload: [
                    'paypal_order_id' => $data['id'],
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
     * @param  array<string, mixed>|null  $address
     * @return array<string, mixed>|null
     */
    private function buildShipping(?array $address): ?array
    {
        if ($address === null) {
            return null;
        }

        $parsed = Address::fromArray($address);
        $paypalAddress = $this->paypalAddress($parsed);

        if ($paypalAddress === null) {
            return null;
        }

        $fullName = mb_trim("{$parsed->firstName} {$parsed->lastName}");

        $shipping = [
            'type' => 'SHIPPING',
            'address' => $paypalAddress,
        ];

        if ($fullName !== '') {
            $shipping['name'] = ['full_name' => $fullName];
        }

        return $shipping;
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @return array<string, mixed>|null
     */
    private function buildPayer(?array $address, ?string $email): ?array
    {
        $payer = [];

        if ($address !== null) {
            $parsed = Address::fromArray($address);

            $name = array_filter([
                'given_name' => $parsed->firstName,
                'surname' => $parsed->lastName,
            ], static fn (string $value): bool => $value !== '');

            if ($name !== []) {
                $payer['name'] = $name;
            }

            $paypalAddress = $this->paypalAddress($parsed);

            if ($paypalAddress !== null) {
                $payer['address'] = $paypalAddress;
            }
        }

        if ($email !== null && $email !== '') {
            $payer['email_address'] = $email;
        }

        return $payer === [] ? null : $payer;
    }

    /**
     * @return array<string, string>|null
     */
    private function paypalAddress(Address $address): ?array
    {
        if ($address->addressLine1 === '' || $address->city === '' || $address->countryCode === '') {
            return null;
        }

        return array_filter([
            'address_line_1' => $address->addressLine1,
            'address_line_2' => $address->addressLine2,
            'admin_area_2' => $address->city,
            'admin_area_1' => $address->state,
            'postal_code' => $address->postalCode,
            'country_code' => mb_strtoupper($address->countryCode),
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->getAccessToken())
            ->connectTimeout(5)
            ->timeout(15)
            ->acceptJson()
            ->asJson();
    }

    private function getAccessToken(): string
    {
        $cacheKey = "paypal_access_token_{$this->gateway->id}";

        return Cache::remember($cacheKey, now()->addHours(8), function (): string {
            [$clientId, $clientSecret] = $this->credentials();

            $response = Http::baseUrl($this->baseUrl())
                ->withBasicAuth($clientId, $clientSecret)
                ->connectTimeout(5)
                ->timeout(15)
                ->asForm()
                ->post('/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                throw new RuntimeException(__('Failed to obtain PayPal access token.'));
            }

            return $response->json('access_token');
        });
    }

    /**
     * @return array{string, string}
     */
    private function credentials(): array
    {
        $clientId = $this->gateway->credentials['client_id'] ?? '';
        $clientSecret = $this->gateway->credentials['client_secret'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new RuntimeException(__('PayPal client ID and secret are not configured.'));
        }

        return [$clientId, $clientSecret];
    }

    private function baseUrl(): string
    {
        $sandbox = $this->gateway->credentials['sandbox'] ?? true;

        return $sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{string|null, array<string, mixed>|null}
     */
    private function extractPaymentMethodDetails(array $data): array
    {
        $paymentSource = $data['payment_source'] ?? [];

        if (isset($paymentSource['card'])) {
            $card = $paymentSource['card'];

            return ['card', array_filter([
                'brand' => $card['brand'] ?? null,
                'last4' => isset($card['last_digits']) ? mb_substr((string) $card['last_digits'], -4) : null,
            ])];
        }

        if (isset($paymentSource['paypal'])) {
            return ['paypal', null];
        }

        $type = array_key_first($paymentSource);

        return $type !== null ? [$type, null] : [null, null];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractApprovalUrl(array $data): ?string
    {
        foreach ($data['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'payer-action') {
                return $link['href'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractCaptureId(array $data): ?string
    {
        foreach ($data['purchase_units'] ?? [] as $unit) {
            foreach ($unit['payments']['captures'] ?? [] as $capture) {
                return $capture['id'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function parseCaptureCompleted(array $resource): WebhookEvent
    {
        $paymentSessionId = $this->extractPaymentSessionIdFromWebhookResource($resource);

        return new WebhookEvent(
            type: 'PAYMENT.CAPTURE.COMPLETED',
            status: PaymentStatus::Paid,
            paymentSessionId: $paymentSessionId,
            gatewayPaymentReference: $resource['id'] ?? null,
            payload: [
                'capture_id' => $resource['id'] ?? null,
                'amount' => $resource['amount']['value'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function parseCaptureDenied(array $resource): WebhookEvent
    {
        $paymentSessionId = $this->extractPaymentSessionIdFromWebhookResource($resource);

        return new WebhookEvent(
            type: 'PAYMENT.CAPTURE.DENIED',
            status: PaymentStatus::Failed,
            paymentSessionId: $paymentSessionId,
            gatewayPaymentReference: $resource['id'] ?? null,
            payload: [
                'capture_id' => $resource['id'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function parseCaptureRefunded(array $resource): WebhookEvent
    {
        $totalRefundedAmount = $resource['seller_payable_breakdown']['total_refunded_amount']['value'] ?? null;
        $captureId = $this->extractCaptureIdFromRefund($resource);
        $refundId = $resource['id'] ?? null;
        $paymentSessionId = $this->extractPaymentSessionIdFromWebhookResource($resource);

        if ($totalRefundedAmount === null && $refundId !== null) {
            $totalRefundedAmount = $this->fetchRefundCumulativeAmount($refundId);
        }

        if ($paymentSessionId === null && $captureId !== null) {
            $paymentSessionId = $this->fetchCapturePaymentSessionId($captureId);
        }

        $grossAmount = $resource['seller_payable_breakdown']['gross_amount']['value'] ?? null;
        $status = $this->resolveRefundStatus($totalRefundedAmount, $grossAmount);

        return new WebhookEvent(
            type: 'PAYMENT.CAPTURE.REFUNDED',
            status: $status,
            paymentSessionId: $paymentSessionId,
            gatewayPaymentReference: $captureId,
            payload: [
                'refund_id' => $refundId,
                'capture_id' => $captureId,
                'amount' => $resource['amount']['value'] ?? null,
                'total_refunded_amount' => $totalRefundedAmount,
            ],
            cumulativeRefundTotal: $totalRefundedAmount,
            gatewayRefundReference: $refundId,
        );
    }

    private function resolveRefundStatus(?string $totalRefundedAmount, ?string $grossAmount): PaymentStatus
    {
        if ($totalRefundedAmount === null || $grossAmount === null) {
            return PaymentStatus::Refunded;
        }

        $refunded = BigDecimal::of($totalRefundedAmount);
        $gross = BigDecimal::of($grossAmount);

        if ($refunded->isGreaterThanOrEqualTo($gross)) {
            return PaymentStatus::Refunded;
        }

        return PaymentStatus::PartiallyRefunded;
    }

    private function fetchRefundCumulativeAmount(string $refundId): ?string
    {
        try {
            $response = $this->client()->get("/v2/payments/refunds/{$refundId}");

            if ($response->failed()) {
                return null;
            }

            return $response->json('seller_payable_breakdown.total_refunded_amount.value');
        } catch (Throwable) {
            return null;
        }
    }

    private function fetchCapturePaymentSessionId(string $captureId): ?string
    {
        try {
            $response = $this->client()->get("/v2/payments/captures/{$captureId}");

            if ($response->failed()) {
                return null;
            }

            return $response->json('custom_id');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function extractCaptureIdFromRefund(array $resource): ?string
    {
        foreach ($resource['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'up') {
                $path = parse_url($link['href'] ?? '', PHP_URL_PATH);

                return is_string($path) ? basename($path) : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function extractPaymentSessionIdFromWebhookResource(array $resource): ?string
    {
        return $resource['custom_id'] ?? $resource['invoice_id'] ?? null;
    }
}
