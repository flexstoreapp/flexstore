<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AdminNewReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Review $review,
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
        $title = (string) $this->review->product->title;

        return (new MailMessage)
            ->subject(__('New review for :product', ['product' => $title]))
            ->view(['emails.admin.new-review.html', 'emails.admin.new-review.text'], [
                'review' => $this->review,
                'productTitle' => $title,
                'viewUrl' => route('admin.reviews.index'),
            ]);
    }
}
