<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderShipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomerOrderFulfilledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly OrderShipment $shipment,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Order #:id fulfilled', ['id' => $this->order->id]))
            ->view(['emails.customer.order-fulfilled.html', 'emails.customer.order-fulfilled.text'], [
                'order' => $this->order,
                'shipment' => $this->shipment,
                'viewUrl' => $this->order->customer_id ? route('account.orders.show', $this->order) : null,
            ]);
    }
}
