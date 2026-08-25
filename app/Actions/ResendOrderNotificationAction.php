<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderActivityType;
use App\Enums\OrderEmailType;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderShipment;
use App\Models\User;
use App\Notifications\CustomerOrderCanceledNotification;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Notifications\CustomerOrderFulfilledNotification;
use App\Notifications\CustomerOrderRefundedNotification;
use App\Notifications\CustomerOrderUpdatedNotification;
use App\Notifications\CustomerTrackingUpdatedNotification;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

final readonly class ResendOrderNotificationAction
{
    public function __construct(
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
    ) {
    }

    public function handle(
        User $user,
        Order $order,
        OrderEmailType $type,
        ?OrderShipment $shipment = null,
        ?OrderRefund $refund = null,
    ): void {
        $this->sendCustomerNotificationAction->handle(
            $this->buildNotification($order, $type, $shipment, $refund),
            $order->customer_id ? $order->customer : null,
            $order->customer_email,
        );

        $this->storeOrderActivityAction->handle(
            order: $order,
            type: OrderActivityType::EmailResent,
            user: $user,
            metadata: ['email_type' => $type->value],
        );
    }

    private function buildNotification(
        Order $order,
        OrderEmailType $type,
        ?OrderShipment $shipment,
        ?OrderRefund $refund,
    ): Notification {
        return match ($type) {
            OrderEmailType::OrderConfirmed => new CustomerOrderConfirmedNotification($order),
            OrderEmailType::OrderUpdated => new CustomerOrderUpdatedNotification($order),
            OrderEmailType::OrderCanceled => new CustomerOrderCanceledNotification($order),
            OrderEmailType::OrderFulfilled => new CustomerOrderFulfilledNotification($order, $this->requireShipment($shipment)),
            OrderEmailType::TrackingUpdated => new CustomerTrackingUpdatedNotification($order, $this->requireShipment($shipment)),
            OrderEmailType::OrderRefunded => new CustomerOrderRefundedNotification($order, $this->requireRefund($refund)),
        };
    }

    private function requireShipment(?OrderShipment $shipment): OrderShipment
    {
        return $shipment ?? throw new InvalidArgumentException('A shipment is required for this notification type.');
    }

    private function requireRefund(?OrderRefund $refund): OrderRefund
    {
        return $refund ?? throw new InvalidArgumentException('A refund is required for this notification type.');
    }
}
