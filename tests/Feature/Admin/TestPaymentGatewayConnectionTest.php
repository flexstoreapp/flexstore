<?php

declare(strict_types=1);

use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Admin\TestPaymentGatewayConnectionController;
use App\Http\Requests\Admin\TestPaymentGatewayConnectionRequest;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\SessionResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;
use App\Payment\PaymentManager;
use Illuminate\Http\Request;

function failingPaymentDriver(): PaymentDriver
{
    return new class() implements PaymentDriver
    {
        public function createSession(CreateSession $session): SessionResult
        {
            return new SessionResult(status: PaymentStatus::Failed, redirectUrl: '');
        }

        public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
        {
            return new VerificationResult(status: $currentStatus);
        }

        public function refund(RefundPayment $refund): RefundResult
        {
            return new RefundResult(status: App\Enums\RefundStatus::Failed, amount: $refund->amount);
        }

        public function verifyWebhook(Request $request): bool
        {
            return false;
        }

        public function parseWebhook(Request $request): WebhookEvent
        {
            return new WebhookEvent(type: 'noop');
        }

        public function testConnection(): bool
        {
            return false;
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
}

covers(TestPaymentGatewayConnectionController::class, TestPaymentGatewayConnectionRequest::class);

uses()->group('payment');

function paymentTestConnectionPayload(): array
{
    return [
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => ['secret_key' => 'sk_test_token'],
    ];
}

test('returns an error when the connection fails', function () {
    PaymentManager::fake(failingPaymentDriver());

    actingAsSuperAdmin()
        ->post(route('admin.payment.gateways.test'), paymentTestConnectionPayload())
        ->assertRedirectBack()
        ->assertSessionHasErrors('connection');
});
