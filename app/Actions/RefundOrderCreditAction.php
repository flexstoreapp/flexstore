<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderActivityType;
use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\PaymentActionException;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\User;
use App\Payment\DTOs\RefundPayment;
use App\Payment\PaymentManager;
use App\Payment\RefundAllocator;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

final readonly class RefundOrderCreditAction
{
    public function __construct(
        private RefundAllocator $refundAllocator,
        private PaymentManager $paymentManager,
        private StoreOrderTransactionAction $storeOrderTransactionAction,
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
    ) {
    }

    public function handle(User $admin, Order $order): OrderRefund
    {
        $order->loadMissing('paymentGateway', 'transactions');

        if (! BigDecimal::of($order->credit_due_total)->isPositive()) {
            throw PaymentActionException::noCreditOwed();
        }

        return DB::transaction(function () use ($admin, $order): OrderRefund {
            $order = Order::query()->with('transactions')->lockForUpdate()->findOrFail($order->id);
            $creditAmount = $order->credit_due_total;

            $refund = $order->refunds()->create([
                'status' => RefundStatus::Completed,
                'amount' => $creditAmount,
                'reason' => __('Credit refund for order total decrease.'),
            ]);

            $refund->items()->create([
                'type' => RefundItemType::Adjustment,
                'amount' => $creditAmount,
                'quantity' => 0,
                'restock' => false,
            ]);

            $gateway = $order->paymentGateway;
            $driver = $gateway ? $this->paymentManager->driver($gateway) : null;
            $supportsRefunds = $driver?->supportsRefunds() ?? false;

            if ($supportsRefunds) {
                $allocations = $this->refundAllocator->allocate($order, $creditAmount);
                [$paymentMethod, $paymentMethodDetails] = $order->getOriginalPaymentMethod();

                foreach ($allocations as $allocation) {
                    $result = $driver->refund(RefundPayment::fromAllocation(
                        allocation: $allocation,
                        currencyCode: $order->currency_code,
                        reason: __('Order total decreased'),
                        idempotencyKey: $refund->id . '_' . $allocation->transactionId,
                    ));

                    $this->storeOrderTransactionAction->handle(
                        order: $order,
                        type: TransactionType::Refund,
                        status: $result->status === RefundStatus::Completed
                            ? TransactionStatus::Success
                            : TransactionStatus::Failed,
                        amount: $allocation->amount,
                        refund: $refund,
                        gatewayReference: $result->gatewayReference,
                        paymentMethod: $paymentMethod,
                        paymentMethodDetails: $paymentMethodDetails,
                        relatedTransactionId: $allocation->transactionId,
                    );
                }
            }

            $newRefundTotal = BigDecimal::of($order->refund_total)
                ->plus($creditAmount)
                ->toScale(4, RoundingMode::HalfUp)
                ->toString();

            $order->update(['refund_total' => $newRefundTotal]);

            $this->reconcileOrderFinancialsAction->handle($order);

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::RefundCompleted,
                user: $admin,
                metadata: ['refund_id' => $refund->id],
            );

            return $refund;
        });
    }
}
