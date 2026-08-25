<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Utilities\InvoicePdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Throwable;

final class CustomerOrderConfirmedNotification extends Notification implements ShouldQueue
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
        $message = (new MailMessage)
            ->subject(__('Order #:id confirmed', ['id' => $this->order->id]))
            ->view(['emails.customer.order-confirmed.html', 'emails.customer.order-confirmed.text'], [
                'order' => $this->order,
                'viewUrl' => $this->order->customer_id ? route('account.orders.show', $this->order) : null,
                'trackUrl' => $this->order->customer_id ? null : URL::signedRoute('orders.track.show', ['order' => $this->order]),
                'downloads' => $this->downloads(),
            ]);

        try {
            $message->attachData(
                InvoicePdfGenerator::generate($this->order),
                "invoice-{$this->order->id}.pdf",
                ['mime' => 'application/pdf'],
            );
        } catch (Throwable $e) {
            report($e);
        }

        return $message;
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
