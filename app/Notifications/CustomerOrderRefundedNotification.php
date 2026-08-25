<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderRefund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomerOrderRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly OrderRefund $refund,
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
            ->subject(__('Refund issued for order #:id', ['id' => $this->order->id]))
            ->view(['emails.customer.order-refunded.html', 'emails.customer.order-refunded.text'], [
                'order' => $this->order,
                'refund' => $this->refund,
                'viewUrl' => $this->order->customer_id ? route('account.orders.show', $this->order) : null,
            ]);
    }
}
