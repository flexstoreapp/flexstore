<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ProcessRefundInput;
use App\Enums\OrderActivityType;
use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use App\Enums\StockMovementReason;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\GatewayRefundFailedException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use App\Models\User;
use App\Notifications\CustomerOrderRefundedNotification;
use App\Payment\DTOs\RefundPayment;
use App\Payment\DTOs\RefundResult;
use App\Payment\PaymentManager;
use App\Payment\RefundAllocator;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ProcessRefundAction
{
    public function __construct(
        private StoreOrderRefundAction $storeOrderRefundAction,
        private StoreOrderTransactionAction $storeOrderTransactionAction,
        private RestockOrderItemsAction $restockOrderItemsAction,
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
        private ReconcileFulfillmentStatusAction $reconcileFulfillmentStatusAction,
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private PaymentManager $paymentManager,
        private RefundAllocator $refundAllocator,
    ) {
    }

    public function handle(User $user, Order $order, ProcessRefundInput $input): OrderRefund
    {
        return $this->process($user, $order, $input);
    }

    private function process(User $user, Order $order, ProcessRefundInput $input): OrderRefund
    {
        $order->load('transactions');

        $isRecordOnly = $input->refundMethod === 'record_only';

        $refund = $this->storeOrderRefundAction->handle($order, $input->toStoreOrderRefundInput());

        $gatewayResults = $isRecordOnly ? null : $this->issueGatewayRefund($refund);

        if ($gatewayResults === null) {
            $this->completeRefund($user, $refund, notifyCustomer: $input->notifyCustomer);

            return $refund;
        }

        $failed = array_filter($gatewayResults, fn (array $r): bool => $r['result']->status === RefundStatus::Failed);
        $succeeded = array_filter($gatewayResults, fn (array $r): bool => $r['result']->status !== RefundStatus::Failed);

        if ($failed !== [] && $succeeded === []) {
            DB::transaction(function () use ($refund): void {
                $refund->items()->delete();
                $refund->delete();
            });

            throw new GatewayRefundFailedException(
                $failed[array_key_first($failed)]['result']->failureReason ?? __('Refund processing failed.'),
            );
        }

        $hasPending = array_filter($succeeded, fn (array $r): bool => $r['result']->status === RefundStatus::Pending);

        if ($hasPending !== [] || $failed !== []) {
            $this->pendingRefund($user, $refund, $gatewayResults);

            return $refund;
        }

        $this->completeRefund(
            $user,
            $refund,
            allocations: array_values($succeeded),
            notifyCustomer: $input->notifyCustomer,
        );

        return $refund;
    }

    /**
     * @param  list<array{allocation: \App\Payment\DTOs\RefundAllocation, result: RefundResult}>|null  $allocations
     */
    private function completeRefund(
        User $user,
        OrderRefund $refund,
        ?array $allocations = null,
        bool $notifyCustomer = true,
    ): void {
        $order = DB::transaction(function () use ($user, $refund, $allocations): Order {
            $refund->update([
                'status' => RefundStatus::Completed,
            ]);

            $order = Order::query()->lockForUpdate()->findOrFail($refund->order_id);

            $this->updateOrderRefundTotal($order, $refund->amount);

            $refund->load('items.orderItem.product', 'items.orderItem.productVariant');
            $hasItemRefunds = $refund->items->where('type', RefundItemType::Product)->isNotEmpty();

            if ($hasItemRefunds) {
                $this->restockFromRefund($user, $refund);
            }

            if ($allocations !== null) {
                [$paymentMethod, $paymentMethodDetails] = $order->getOriginalPaymentMethod();

                foreach ($allocations as $entry) {
                    $this->storeOrderTransactionAction->handle(
                        order: $order,
                        type: TransactionType::Refund,
                        status: TransactionStatus::Success,
                        amount: $entry['allocation']->amount,
                        refund: $refund,
                        gatewayReference: $entry['result']->gatewayReference,
                        paymentMethod: $paymentMethod,
                        paymentMethodDetails: $paymentMethodDetails,
                        relatedTransactionId: $entry['allocation']->transactionId,
                    );
                }
            }

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::RefundCompleted,
                user: $user,
                metadata: ['refund_id' => $refund->id],
            );

            $this->reconcileOrderFinancialsAction->handle($order);

            if ($hasItemRefunds) {
                $this->reconcileFulfillmentStatusAction->handle($order);
            }

            return $order;
        });

        if ($notifyCustomer) {
            $this->sendCustomerNotificationAction->handle(
                new CustomerOrderRefundedNotification($order, $refund),
                $order->customer_id ? $order->customer : null,
                $order->customer_email,
            );
        }
    }

    /**
     * @param  list<array{allocation: \App\Payment\DTOs\RefundAllocation, result: RefundResult}>  $allocations
     */
    private function pendingRefund(User $user, OrderRefund $refund, array $allocations): void
    {
        DB::transaction(function () use ($user, $refund, $allocations): void {
            $refund->update([
                'status' => RefundStatus::Pending,
            ]);

            [$paymentMethod, $paymentMethodDetails] = $refund->order->getOriginalPaymentMethod();

            foreach ($allocations as $entry) {
                if ($entry['result']->status === RefundStatus::Failed) {
                    continue;
                }

                $this->storeOrderTransactionAction->handle(
                    order: $refund->order,
                    type: TransactionType::Refund,
                    status: TransactionStatus::Pending,
                    amount: $entry['allocation']->amount,
                    refund: $refund,
                    gatewayReference: $entry['result']->gatewayReference,
                    paymentMethod: $paymentMethod,
                    paymentMethodDetails: $paymentMethodDetails,
                    relatedTransactionId: $entry['allocation']->transactionId,
                );
            }

            $this->storeOrderActivityAction->handle(
                order: $refund->order,
                type: OrderActivityType::RefundPending,
                user: $user,
                metadata: ['refund_id' => $refund->id],
            );
        });
    }

    /**
     * @return list<array{allocation: \App\Payment\DTOs\RefundAllocation, result: RefundResult}>|null
     */
    private function issueGatewayRefund(OrderRefund $refund): ?array
    {
        $order = $refund->order;
        $gateway = $order->paymentGateway;

        if (! $gateway) {
            return null;
        }

        $driver = $this->paymentManager->driver($gateway);

        if (! $driver->supportsRefunds()) {
            return null;
        }

        $allocations = $this->refundAllocator->allocate($order, $refund->amount);

        if ($allocations === []) {
            return null;
        }

        $results = [];

        foreach ($allocations as $index => $allocation) {
            try {
                $result = $driver->refund(RefundPayment::fromAllocation(
                    allocation: $allocation,
                    currencyCode: $order->currency_code,
                    orderId: $order->id,
                    reason: $refund->reason,
                    idempotencyKey: $refund->id . '_' . $index,
                ));
            } catch (Throwable $e) {
                $result = new RefundResult(
                    status: RefundStatus::Failed,
                    amount: $allocation->amount,
                    failureReason: $e->getMessage(),
                );
            }

            $results[] = ['allocation' => $allocation, 'result' => $result];
        }

        return $results;
    }

    private function updateOrderRefundTotal(Order $order, string $refundAmount): void
    {
        $newTotal = BigDecimal::of($order->refund_total)
            ->plus($refundAmount)
            ->toScale(4, RoundingMode::HalfUp)
            ->toString();

        $order->update(['refund_total' => $newTotal]);
    }

    private function restockFromRefund(User $user, OrderRefund $refund): void
    {
        /** @var \Illuminate\Support\Collection<int, array{item: OrderItem, quantity: int}> $entries */
        $entries = $refund->items
            ->where('type', RefundItemType::Product)
            ->filter(fn (OrderRefundItem $refundItem): bool => $refundItem->restock && $refundItem->orderItem !== null)
            ->map(fn (OrderRefundItem $refundItem): array => [
                'item' => $refundItem->orderItem,
                'quantity' => (int) $refundItem->quantity,
            ]);

        $this->restockOrderItemsAction->handle(
            $user,
            $entries,
            StockMovementReason::Return,
            OrderRefund::class,
            $refund->id,
        );
    }
}
