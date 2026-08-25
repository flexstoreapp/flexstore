<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AdminLowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Product $product,
        private readonly ?ProductVariant $variant = null,
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
        $target = $this->variant ?? $this->product;
        $name = $this->variant instanceof ProductVariant
            ? $this->product->title . ' - ' . $this->variant->title
            : $this->product->title;

        return (new MailMessage)
            ->subject(__('Low stock alert: :name', ['name' => $name]))
            ->view(['emails.admin.low-stock.html', 'emails.admin.low-stock.text'], [
                'name' => $name,
                'stock' => $target->stock,
                'viewUrl' => route('admin.inventory.index'),
            ]);
    }
}
