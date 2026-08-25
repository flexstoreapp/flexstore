<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Notifications\CustomerDigitalDownloadsReadyNotification;

final readonly class DeliverOrderDownloadsAction
{
    public function __construct(
        private FulfillDigitalItemsAction $fulfillDigitalItemsAction,
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
    ) {
    }

    public function handle(Order $order): void
    {
        if ($order->payment_status !== PaymentStatus::Paid) {
            return;
        }

        $this->fulfillDigitalItemsAction->handle($order);

        if (! $order->itemDownloads()->exists()) {
            return;
        }

        if (! Setting::getValue('notification_customer_order_confirmed')) {
            return;
        }

        $this->sendCustomerNotificationAction->handle(
            new CustomerDigitalDownloadsReadyNotification($order),
            $order->customer_id ? $order->customer : null,
            $order->customer_email,
        );
    }
}
