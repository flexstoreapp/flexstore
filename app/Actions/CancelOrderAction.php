<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CancelOrderInput;
use App\DTOs\ProcessRefundInput;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderConflictException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminOrderCanceledNotification;
use App\Notifications\CustomerOrderCanceledNotification;
use App\Queries\RefundableOrderDataQuery;
use Illuminate\Support\Facades\DB;

final readonly class CancelOrderAction
{
    public function __construct(
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private ProcessRefundAction $processRefundAction,
        private RestockOrderItemsAction $restockOrderItemsAction,
        private DecrementCouponUsageAction $decrementCouponUsageAction,
        private RecalculateCustomerLifetimeValueAction $recalculateCustomerLifetimeValueAction,
        private SendAdminNotificationAction $sendAdminNotificationAction,
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
        private RefundableOrderDataQuery $refundableOrderDataQuery,
    ) {
    }

    public function handle(User $user, Order $order, CancelOrderInput $input): Order
    {
        if ($order->is_canceled) {
            throw OrderConflictException::orderAlreadyCanceled();
        }

        if ($order->fulfillment_status === FulfillmentStatus::Fulfilled) {
            throw OrderConflictException::fulfilledOrderCannotBeCanceled();
        }

        $order->load('items.product', 'items.productVariant');

        $order = DB::transaction(function () use ($user, $order, $input): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $order->update([
                'canceled_at' => now(),
                'cancellation_reason' => $input->reason,
                'cancellation_note' => $input->reasonNote,
            ]);

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::OrderCanceled,
                user: $user,
                metadata: array_filter([
                    'fulfillment_status' => $order->fulfillment_status->value,
                    'cancellation_reason' => $input->reason->value,
                    'cancellation_note' => $input->reasonNote,
                ]),
            );

            if ($input->refund && $order->is_refundable) {
                $this->processFullRefund($user, $order, $input->restock, $input->notifyCustomer);
            } else {
                if ($this->canCancelPayment($order->payment_status)) {
                    $order->update(['payment_status' => PaymentStatus::Canceled]);
                    $this->reconcileOrderFinancialsAction->handle($order);
                }

                if ($input->restock) {
                    $entries = $order->items->map(fn (OrderItem $item): array => [
                        'item' => $item,
                        'quantity' => (int) $item->quantity,
                    ]);

                    $this->restockOrderItemsAction->handle(
                        $user,
                        $entries,
                        StockMovementReason::Cancellation,
                        Order::class,
                        $order->id,
                    );
                }
            }

            if ($order->coupon_id && $order->coupon) {
                $this->decrementCouponUsageAction->handle($order->coupon);
            }

            $this->recalculateCustomerLifetimeValueAction->handle($order->customer);

            return $order;
        });

        if (Setting::getValue('notification_admin_order_canceled')) {
            $this->sendAdminNotificationAction->handle(new AdminOrderCanceledNotification($order));
        }

        if ($input->notifyCustomer) {
            $this->sendCustomerNotificationAction->handle(
                new CustomerOrderCanceledNotification($order),
                $order->customer_id ? $order->customer : null,
                $order->customer_email,
            );
        }

        return $order->refresh();
    }

    private function canCancelPayment(PaymentStatus $status): bool
    {
        return in_array($status, [
            PaymentStatus::Unpaid,
            PaymentStatus::Failed,
        ], true);
    }

    private function processFullRefund(User $user, Order $order, bool $restock, bool $notifyCustomer): void
    {
        $refundableData = $this->refundableOrderDataQuery->execute($order);

        $items = [];
        foreach ($refundableData['refundable_quantities'] as $orderItemId => $quantity) {
            if ($quantity > 0) {
                $items[] = [
                    'order_item_id' => $orderItemId,
                    'quantity' => $quantity,
                ];
            }
        }

        $this->processRefundAction->handle($user, $order, ProcessRefundInput::fromArray([
            'items' => $items,
            'shipping_amount' => $refundableData['refundable_shipping_amount'],
            'restock' => $restock,
            'notify_customer' => $notifyCustomer,
        ]));
    }
}
