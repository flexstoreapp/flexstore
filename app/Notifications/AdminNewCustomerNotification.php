<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AdminNewCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $customer,
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
            ->subject(__('New customer registration'))
            ->view(['emails.admin.new-customer.html', 'emails.admin.new-customer.text'], [
                'customer' => $this->customer,
                'viewUrl' => route('admin.customers.edit', $this->customer),
            ]);
    }
}
