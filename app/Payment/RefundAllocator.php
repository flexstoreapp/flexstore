<?php

declare(strict_types=1);

namespace App\Payment;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Payment\DTOs\RefundAllocation;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class RefundAllocator
{
    /**
     * @return list<RefundAllocation>
     */
    public function allocate(Order $order, string $amount): array
    {
        $order->loadMissing('transactions');

        $collectTransactions = $order->transactions
            ->filter(fn (OrderTransaction $t): bool => $t->status === TransactionStatus::Success
                && $t->type === TransactionType::Sale)
            ->sortBy('id');

        $refundTransactions = $order->transactions
            ->filter(fn (OrderTransaction $t): bool => $t->type === TransactionType::Refund
                && in_array($t->status, [TransactionStatus::Success, TransactionStatus::Pending], true));

        $refundedByTransaction = [];
        foreach ($refundTransactions as $refund) {
            if ($refund->related_transaction_id !== null) {
                $key = $refund->related_transaction_id;
                $refundedByTransaction[$key] = BigDecimal::of($refundedByTransaction[$key] ?? '0')
                    ->plus($refund->amount)
                    ->toString();
            }
        }

        $remaining = BigDecimal::of($amount);
        $allocations = [];

        foreach ($collectTransactions as $transaction) {
            if ($remaining->isZero()) {
                break;
            }

            $alreadyRefunded = BigDecimal::of($refundedByTransaction[$transaction->id] ?? '0');
            $refundable = BigDecimal::of($transaction->amount)->minus($alreadyRefunded);

            if ($refundable->isNegativeOrZero()) {
                continue;
            }

            $allocateAmount = $remaining->isGreaterThan($refundable) ? $refundable : $remaining;

            $allocations[] = new RefundAllocation(
                transactionId: $transaction->id,
                gatewayReference: $transaction->gateway_reference,
                amount: $allocateAmount->toScale(4, RoundingMode::HalfUp)->toString(),
            );

            $remaining = $remaining->minus($allocateAmount);
        }

        return $allocations;
    }
}
