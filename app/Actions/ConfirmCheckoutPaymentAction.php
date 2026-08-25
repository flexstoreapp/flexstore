<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\PaymentSettlement;
use App\Enums\PaymentSessionPurpose;
use App\Enums\PaymentSessionStatus;
use App\Enums\PaymentStatus;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentSession;
use App\Payment\PaymentManager;
use Brick\Math\BigDecimal;
use Throwable;

final readonly class ConfirmCheckoutPaymentAction
{
    public function __construct(
        private ConfirmCheckoutSessionAction $confirmCheckoutSessionAction,
        private PaymentManager $paymentManager,
    ) {
    }

    public function handle(CheckoutSession $session): ?Order
    {
        $paymentSession = $this->latestPaymentSession($session);
        $settlement = $this->verify($session, $paymentSession?->gateway_reference);

        if (in_array($settlement->status, [PaymentStatus::Failed, PaymentStatus::Canceled], true)) {
            return null;
        }

        try {
            if ($paymentSession instanceof PaymentSession && $paymentSession->status === PaymentSessionStatus::Completed) {
                $existingOrder = Order::query()->find($session->order_id);

                return $existingOrder instanceof Order ? $existingOrder : null;
            }

            $order = $this->confirmCheckoutSessionAction->handle($session, $settlement);

            if ($order instanceof Order && $paymentSession instanceof PaymentSession) {
                $paymentSession->update([
                    'status' => PaymentSessionStatus::Completed,
                    'completed_at' => now(),
                ]);
            }

            return $order instanceof Order ? $order : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function latestPaymentSession(CheckoutSession $session): ?PaymentSession
    {
        return PaymentSession::query()
            ->where('owner_type', CheckoutSession::class)
            ->where('owner_id', $session->id)
            ->where('purpose', PaymentSessionPurpose::Checkout)
            ->latest()
            ->latest('id')
            ->first();
    }

    private function verify(CheckoutSession $session, ?string $gatewayReference): PaymentSettlement
    {
        if ($session->paymentGateway === null) {
            return new PaymentSettlement(
                status: BigDecimal::of($session->total)->isZero() ? PaymentStatus::Paid : PaymentStatus::Unpaid,
            );
        }

        try {
            $verification = $this->paymentManager
                ->driver($session->paymentGateway)
                ->verifyPayment($gatewayReference, PaymentStatus::Unpaid);

            return new PaymentSettlement(
                status: $verification->status,
                gatewayReference: $verification->gatewayReference ?? $gatewayReference,
                paymentMethod: $verification->paymentMethod,
                paymentMethodDetails: $verification->paymentMethodDetails,
            );
        } catch (Throwable) {
            return new PaymentSettlement(status: PaymentStatus::Unpaid);
        }
    }
}
