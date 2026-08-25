<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomerOrderCanceledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
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
            ->subject(__('Order #:id canceled', ['id' => $this->order->id]))
            ->view(['emails.customer.order-canceled.html', 'emails.customer.order-canceled.text'], [
                'order' => $this->order,
                'viewUrl' => $this->order->customer_id ? route('account.orders.show', $this->order) : null,
            ]);
    }
}
