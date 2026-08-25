<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderActivityType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\PaymentActionException;
use App\Models\Order;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

final readonly class RecordPaymentAction
{
    public function __construct(
        private StoreOrderTransactionAction $storeOrderTransactionAction,
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private DeliverOrderDownloadsAction $deliverOrderDownloadsAction,
    ) {
    }

    public function handle(User $admin, Order $order): void
    {
        if (! BigDecimal::of($order->balance_due_total)->isPositive()) {
            throw PaymentActionException::noOutstandingBalance();
        }

        DB::transaction(function () use ($admin, $order): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! BigDecimal::of($order->balance_due_total)->isPositive()) {
                throw PaymentActionException::noOutstandingBalance();
            }

            $transaction = $this->storeOrderTransactionAction->handle(
                order: $order,
                type: TransactionType::Sale,
                status: TransactionStatus::Success,
                amount: $order->balance_due_total,
                isManualEntry: true,
            );

            $this->reconcileOrderFinancialsAction->handle($order);

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::PaymentReceived,
                user: $admin,
                metadata: [
                    'transaction_id' => $transaction->id,
                ],
            );
        });

        $this->deliverOrderDownloadsAction->handle($order->refresh());
    }
}
