<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderActivityType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\PaymentActionException;
use App\Models\Order;
use App\Models\User;
use App\Payment\PaymentManager;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

final readonly class VoidPaymentAction
{
    public function __construct(
        private PaymentManager $paymentManager,
        private StoreOrderTransactionAction $storeOrderTransactionAction,
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
    ) {
    }

    public function handle(Order $order, User $user): void
    {
        $gateway = $order->paymentGateway;

        if ($gateway === null) {
            throw PaymentActionException::noPaymentGateway();
        }

        if (! $this->paymentManager->driver($gateway)->isManual()) {
            throw PaymentActionException::onlyManualSupportsVoiding();
        }

        if (! BigDecimal::of($order->paid_total)->isPositive()) {
            throw PaymentActionException::noRecordedPayments();
        }

        DB::transaction(function () use ($user, $order): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $transaction = $this->storeOrderTransactionAction->handle(
                order: $order,
                type: TransactionType::Void,
                status: TransactionStatus::Success,
                amount: $order->paid_total,
                isManualEntry: true,
            );

            $this->reconcileOrderFinancialsAction->handle($order);

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::PaymentVoided,
                user: $user,
                metadata: [
                    'transaction_id' => $transaction->id,
                ],
            );
        });
    }
}
