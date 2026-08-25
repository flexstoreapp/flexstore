<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderActivityType;
use App\Enums\RefundStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\OrderRefund;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

final readonly class HandleExternalRefundAction
{
    public function __construct(
        private StoreOrderTransactionAction $storeOrderTransactionAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
    ) {
    }

    public function handle(Order $order, string $cumulativeRefundTotal, ?string $gatewayReference = null): void
    {
        DB::transaction(function () use ($order, $cumulativeRefundTotal, $gatewayReference): void {
            $order = Order::query()->with('transactions')->lockForUpdate()->findOrFail($order->id);

            $cumulative = BigDecimal::of($cumulativeRefundTotal)->toScale(4, RoundingMode::HalfUp);

            if ($cumulative->isNegative()) {
                return;
            }

            $paidTotal = BigDecimal::of($order->paid_total);
            $capAmount = $paidTotal->isPositive() ? $paidTotal : BigDecimal::of($order->total);

            if ($cumulative->isGreaterThan($capAmount)) {
                $cumulative = $capAmount;
            }

            $currentRefundTotal = BigDecimal::of($order->refund_total);

            if ($cumulative->isEqualTo($currentRefundTotal)) {
                return;
            }

            if ($cumulative->isGreaterThan($currentRefundTotal)) {
                $this->applyRefundIncrease($order, $cumulative, $currentRefundTotal, $gatewayReference);
            } else {
                $this->applyRefundReversal($order, $cumulative, $currentRefundTotal, $gatewayReference);
            }

            $this->reconcileOrderFinancialsAction->handle($order);
        });
    }

    private function applyRefundIncrease(Order $order, BigDecimal $cumulative, BigDecimal $currentRefundTotal, ?string $gatewayReference): void
    {
        $delta = $cumulative->minus($currentRefundTotal)->toScale(4, RoundingMode::HalfUp);

        $refund = OrderRefund::query()->create([
            'order_id' => $order->id,
            'status' => RefundStatus::Completed,
            'amount' => $delta->toString(),
            'reason' => __('Refund initiated from payment gateway.'),
        ]);

        [$paymentMethod, $paymentMethodDetails] = $order->getOriginalPaymentMethod();

        $this->storeOrderTransactionAction->handle(
            order: $order,
            type: TransactionType::Refund,
            status: TransactionStatus::Success,
            amount: $delta->toString(),
            refund: $refund,
            gatewayReference: $gatewayReference,
            paymentMethod: $paymentMethod,
            paymentMethodDetails: $paymentMethodDetails,
        );

        $order->update(['refund_total' => $cumulative->toString()]);

        $this->storeOrderActivityAction->handle(
            order: $order,
            type: OrderActivityType::RefundCompleted,
            metadata: ['refund_id' => $refund->id],
        );
    }

    private function applyRefundReversal(Order $order, BigDecimal $cumulative, BigDecimal $currentRefundTotal, ?string $gatewayReference): void
    {
        $reversedAmount = $currentRefundTotal->minus($cumulative)->toScale(4, RoundingMode::HalfUp);

        $refund = OrderRefund::query()->create([
            'order_id' => $order->id,
            'status' => RefundStatus::Failed,
            'amount' => $reversedAmount->toString(),
            'reason' => __('Refund canceled by payment provider.'),
        ]);

        [$paymentMethod, $paymentMethodDetails] = $order->getOriginalPaymentMethod();

        $this->storeOrderTransactionAction->handle(
            order: $order,
            type: TransactionType::Refund,
            status: TransactionStatus::Failed,
            amount: $reversedAmount->toString(),
            refund: $refund,
            gatewayReference: $gatewayReference,
            paymentMethod: $paymentMethod,
            paymentMethodDetails: $paymentMethodDetails,
        );

        $order->update(['refund_total' => $cumulative->toString()]);
    }
}
