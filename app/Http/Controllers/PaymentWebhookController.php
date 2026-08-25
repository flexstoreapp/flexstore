<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConfirmCheckoutSessionAction;
use App\Actions\HandleExternalRefundAction;
use App\DTOs\PaymentSettlement;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentSessionPurpose;
use App\Enums\PaymentSessionStatus;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentSession;
use App\Payment\DTOs\WebhookEvent;
use App\Payment\PaymentManager;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class PaymentWebhookController
{
    public function __construct(
        private PaymentManager $paymentManager,
        private ConfirmCheckoutSessionAction $confirmCheckoutSessionAction,
        private HandleExternalRefundAction $handleExternalRefundAction,
    ) {
    }

    public function __invoke(Request $request, string $driver): Response
    {
        $driverEnum = PaymentGatewayDriver::tryFrom($driver);

        if ($driverEnum === null) {
            return response('Unknown driver', 400);
        }

        $gateway = PaymentGateway::query()->where('driver', $driverEnum)->first();

        if ($gateway === null) {
            return response('Gateway not found', 404);
        }

        $driver = $this->paymentManager->driver($gateway);

        if (! $driver->verifyWebhook($request)) {
            return response('Invalid signature', 403);
        }

        $event = $driver->parseWebhook($request);

        if ($event->isRefundEvent()) {
            $this->handleRefund($event, $gateway);
        } elseif ($event->shouldConfirmPaymentSession()) {
            $this->handlePaymentSessionConfirmation($event);
        }

        return response()->noContent();
    }

    private function handleRefund(WebhookEvent $event, PaymentGateway $gateway): void
    {
        if (! $gateway->sync_external_refunds) {
            return;
        }

        $order = $this->resolveOrderForRefund($event, $gateway);

        if (! $order instanceof Order) {
            Log::warning('Refund webhook received for unknown order.', [
                'gateway_payment_reference' => $event->gatewayPaymentReference,
                'payment_session_id' => $event->paymentSessionId,
                'refund_amount' => $event->cumulativeRefundTotal,
            ]);

            return;
        }

        $this->handleExternalRefundAction->handle($order, (string) $event->cumulativeRefundTotal, $event->gatewayRefundReference);
    }

    private function resolveOrderForRefund(WebhookEvent $event, PaymentGateway $gateway): ?Order
    {
        $references = array_filter(array_unique([$event->gatewayPaymentReference, $event->gatewayOrderReference]));

        if ($references !== []) {
            $order = Order::query()
                ->where('payment_gateway_id', $gateway->id)
                ->whereHas('transactions', fn (Builder $q) => $q->whereIn('gateway_reference', $references))
                ->first();

            if ($order !== null) {
                return $order;
            }
        }

        if ($event->paymentSessionId !== null) {
            $paymentSession = PaymentSession::query()->find($event->paymentSessionId);

            if ($paymentSession !== null) {
                return match ($paymentSession->purpose) {
                    PaymentSessionPurpose::Checkout => CheckoutSession::query()
                        ->where('id', $paymentSession->owner_id)
                        ->whereNotNull('order_id')
                        ->first()?->order,
                };
            }
        }

        return null;
    }

    private function handlePaymentSessionConfirmation(WebhookEvent $event): void
    {
        $paymentSession = PaymentSession::query()->find($event->paymentSessionId);

        if ($paymentSession === null) {
            return;
        }

        if ($paymentSession->status === PaymentSessionStatus::Completed) {
            return;
        }

        match ($paymentSession->purpose) {
            PaymentSessionPurpose::Checkout => $this->confirmCheckoutPayment($paymentSession, $event),
        };
    }

    private function confirmCheckoutPayment(PaymentSession $paymentSession, WebhookEvent $event): void
    {
        $checkoutSession = CheckoutSession::query()->find($paymentSession->owner_id);

        if ($checkoutSession === null) {
            return;
        }

        try {
            $this->confirmCheckoutSessionAction->handle($checkoutSession, new PaymentSettlement(
                status: $event->status, // @phpstan-ignore argument.type
                gatewayReference: $event->gatewayPaymentReference,
                paymentMethod: $event->paymentMethod,
                paymentMethodDetails: $event->paymentMethodDetails,
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
