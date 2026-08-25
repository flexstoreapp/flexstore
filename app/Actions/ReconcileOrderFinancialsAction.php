<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\OrderTransaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

final readonly class ReconcileOrderFinancialsAction
{
    public function __construct(
        private RecalculateCustomerLifetimeValueAction $recalculateCustomerLifetimeValueAction,
    ) {
    }

    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $order->load('transactions');

            $previousStatus = $order->payment_status;

            $collected = $order->transactions
                ->filter(fn (OrderTransaction $t): bool => $t->status === TransactionStatus::Success
                    && $t->type === TransactionType::Sale)
                ->reduce(
                    fn (BigDecimal $carry, OrderTransaction $t): BigDecimal => $carry->plus($t->amount),
                    BigDecimal::zero(),
                );

            $voided = $order->transactions
                ->filter(fn (OrderTransaction $t): bool => $t->status === TransactionStatus::Success
                    && $t->type === TransactionType::Void)
                ->reduce(
                    fn (BigDecimal $carry, OrderTransaction $t): BigDecimal => $carry->plus($t->amount),
                    BigDecimal::zero(),
                );

            $transactionPaidTotal = $collected->minus($voided);
            if ($transactionPaidTotal->isNegative()) {
                $transactionPaidTotal = BigDecimal::zero();
            }

            $paidTotal = $collected->isZero()
                ? BigDecimal::of($order->paid_total)
                : $transactionPaidTotal;

            $refundedTotal = $order->transactions
                ->filter(fn (OrderTransaction $t): bool => $t->status === TransactionStatus::Success
                    && $t->type === TransactionType::Refund)
                ->reduce(
                    fn (BigDecimal $carry, OrderTransaction $t): BigDecimal => $carry->plus($t->amount),
                    BigDecimal::zero(),
                );

            $refundTotal = BigDecimal::max($refundedTotal, BigDecimal::of($order->refund_total));
            $orderTotal = BigDecimal::of($order->total);

            $netPaidTotal = BigDecimal::max($paidTotal->minus($refundTotal), BigDecimal::zero())->toScale(4, RoundingMode::HalfUp);
            $balanceDue = $orderTotal->minus($paidTotal);
            $balanceDueTotal = ($balanceDue->isPositive() ? $balanceDue : BigDecimal::zero())->toScale(4, RoundingMode::HalfUp);
            $creditDue = $netPaidTotal->minus($orderTotal);
            $creditDueTotal = ($creditDue->isPositive() ? $creditDue : BigDecimal::zero())->toScale(4, RoundingMode::HalfUp);

            $paymentStatus = $this->derivePaymentStatus($order->payment_status, $collected, $paidTotal, $netPaidTotal, $refundTotal, $orderTotal);

            $order->update([
                'paid_total' => $paidTotal->toScale(4, RoundingMode::HalfUp)->toString(),
                'net_paid_total' => $netPaidTotal->toString(),
                'balance_due_total' => $balanceDueTotal->toString(),
                'credit_due_total' => $creditDueTotal->toString(),
                'payment_status' => $paymentStatus,
            ]);

            if ($paymentStatus !== $previousStatus) {
                $order->loadMissing('customer');
                $this->recalculateCustomerLifetimeValueAction->handle($order->customer);
            }

            return $order;
        });
    }

    private function derivePaymentStatus(
        PaymentStatus $currentStatus,
        BigDecimal $collected,
        BigDecimal $paidTotal,
        BigDecimal $netPaidTotal,
        BigDecimal $refundTotal,
        BigDecimal $orderTotal,
    ): PaymentStatus {
        if (in_array($currentStatus, [PaymentStatus::Canceled, PaymentStatus::Failed, PaymentStatus::Unpaid], true)
            && $collected->isZero()) {
            return $currentStatus;
        }

        if ($paidTotal->isZero()) {
            return $refundTotal->isPositive() ? PaymentStatus::Refunded : PaymentStatus::Unpaid;
        }

        if ($netPaidTotal->isNegativeOrZero() && $refundTotal->isPositive()) {
            return PaymentStatus::Refunded;
        }

        if ($refundTotal->isPositive() && $netPaidTotal->isPositive()) {
            return PaymentStatus::PartiallyRefunded;
        }

        if ($netPaidTotal->isGreaterThanOrEqualTo($orderTotal)) {
            return PaymentStatus::Paid;
        }

        if ($netPaidTotal->isPositive()) {
            return PaymentStatus::PartiallyPaid;
        }

        return $currentStatus;
    }
}
