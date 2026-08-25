<?php

declare(strict_types=1);

use App\Actions\CreateCheckoutPaymentSessionAction;
use App\Enums\CheckoutSessionStatus;
use App\Enums\PaymentSessionStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\PaymentSession;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\SessionResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;
use App\Payment\PaymentManager;
use Illuminate\Http\Request;

covers(CreateCheckoutPaymentSessionAction::class);

uses()->group('actions', 'checkout');

test('cancels the checkout session when the gateway reports failure', function () {
    $failingDriver = new class implements PaymentDriver
    {
        public function createSession(CreateSession $session): SessionResult
        {
            return new SessionResult(
                status: PaymentStatus::Failed,
                redirectUrl: '',
                gatewayReference: 'mock_failed',
                payload: ['message' => 'declined'],
                failureReason: 'declined',
            );
        }

        public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
        {
            return new VerificationResult(status: $currentStatus);
        }

        public function refund(RefundPayment $refund): RefundResult
        {
            return new RefundResult(status: RefundStatus::Failed, amount: $refund->amount, gatewayReference: null);
        }

        public function verifyWebhook(Request $request): bool
        {
            return true;
        }

        public function parseWebhook(Request $request): WebhookEvent
        {
            return new WebhookEvent(type: 'mock', status: null, paymentSessionId: null, gatewayPaymentReference: null, payload: []);
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
            return false;
        }
    };

    PaymentManager::fake($failingDriver);

    $cart = Cart::factory()->create();
    $checkoutSession = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'total' => '42.0000',
        'currency_code' => 'USD',
        'customer_email' => 'customer@example.com',
    ]);

    $result = app(CreateCheckoutPaymentSessionAction::class)->handle($checkoutSession, $cart, null);

    expect($result->paymentSession->status)->toBe(PaymentStatus::Failed)
        ->and($checkoutSession->refresh()->status)->toBe(CheckoutSessionStatus::Canceled);

    $persistedSession = PaymentSession::query()
        ->where('owner_type', CheckoutSession::class)
        ->where('owner_id', $checkoutSession->id)
        ->firstOrFail();

    expect($persistedSession->status)->toBe(PaymentSessionStatus::Failed)
        ->and($persistedSession->gateway_reference)->toBe('mock_failed');
});
