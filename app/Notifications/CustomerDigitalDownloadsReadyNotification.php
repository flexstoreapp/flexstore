<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItemDownload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class CustomerDigitalDownloadsReadyNotification extends Notification implements ShouldQueue
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
            ->subject(__('Your downloads for order #:id are ready', ['id' => $this->order->id]))
            ->view(['emails.customer.downloads-ready.html', 'emails.customer.downloads-ready.text'], [
                'order' => $this->order,
                'viewUrl' => $this->order->customer_id ? route('account.orders.show', $this->order) : null,
                'downloads' => $this->downloads(),
            ]);
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    private function downloads(): array
    {
        $this->order->loadMissing('itemDownloads');

        return array_values($this->order->itemDownloads
            ->map(fn (OrderItemDownload $download): array => [
                'name' => $download->name,
                'url' => URL::signedRoute('downloads.show', ['download' => $download->token]),
            ])
            ->all());
    }
}
