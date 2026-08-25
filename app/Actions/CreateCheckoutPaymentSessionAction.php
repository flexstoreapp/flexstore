<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CheckoutInitiationResult;
use App\Enums\PaymentSessionPurpose;
use App\Enums\PaymentSessionStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\PaymentSession;
use App\Payment\DTOs\CreateSession;
use App\Payment\PaymentManager;

final readonly class CreateCheckoutPaymentSessionAction
{
    public function __construct(
        private PaymentManager $paymentManager,
        private CancelCheckoutSessionAction $cancelCheckoutSessionAction,
    ) {
    }

    public function handle(
        CheckoutSession $checkoutSession,
        Cart $cart,
        ?string $cardToken,
        bool $cancelOnFailure = true,
    ): CheckoutInitiationResult {
        $paymentGateway = PaymentGateway::query()->find($checkoutSession->payment_gateway_id);
        assert($paymentGateway instanceof PaymentGateway);

        $paymentSessionModel = PaymentSession::query()->create([
            'payment_gateway_id' => $paymentGateway->id,
            'owner_type' => CheckoutSession::class,
            'owner_id' => $checkoutSession->id,
            'purpose' => PaymentSessionPurpose::Checkout,
            'currency_code' => $checkoutSession->currency_code,
            'amount' => $checkoutSession->total,
            'customer_email' => $checkoutSession->customer_email,
            'status' => PaymentSessionStatus::Pending,
            'return_url' => '',
        ]);

        $paymentSessionModel->setRelation('paymentGateway', $paymentGateway);

        $createSessionDTO = CreateSession::fromCheckoutPaymentSession(
            $paymentSessionModel,
            $checkoutSession,
            ['card_token' => $cardToken],
        );

        $paymentSessionModel->update([
            'return_url' => $createSessionDTO->redirectUrls->returnUrl,
            'cancel_url' => $createSessionDTO->redirectUrls->cancelUrl,
            'failure_url' => $createSessionDTO->redirectUrls->failureUrl,
            'webhook_url' => $createSessionDTO->callbackUrls->webhookUrl,
            'description' => $createSessionDTO->description,
            'metadata' => $createSessionDTO->metadata,
        ]);

        $driver = $this->paymentManager->driver($paymentGateway);
        $paymentSession = $driver->createSession($createSessionDTO);

        if ($paymentSession->status === PaymentStatus::Failed) {
            $paymentSessionModel->update([
                'status' => PaymentSessionStatus::Failed,
                'gateway_reference' => $paymentSession->gatewayReference,
                'payload' => $paymentSession->payload,
            ]);
            if ($cancelOnFailure) {
                $this->cancelCheckoutSessionAction->handle($checkoutSession);
            }

            return new CheckoutInitiationResult(
                checkoutSession: $checkoutSession,
                paymentSession: $paymentSession,
                cart: $cart,
            );
        }

        $paymentSessionModel->update([
            'gateway_reference' => $paymentSession->gatewayReference,
            'payload' => $paymentSession->payload,
        ]);

        return new CheckoutInitiationResult(
            checkoutSession: $checkoutSession,
            paymentSession: $paymentSession,
            cart: $cart,
        );
    }
}
