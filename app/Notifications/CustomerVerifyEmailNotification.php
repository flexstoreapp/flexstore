<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;

final class CustomerVerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        assert($notifiable instanceof User);

        $verifyUrl = URL::temporarySignedRoute(
            'account.verification.verify',
            Date::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => hash('sha1', (string) $notifiable->getEmailForVerification()),
            ],
        );

        return (new MailMessage)
            ->subject(__('Verify your email address'))
            ->view(['emails.auth.verify-email.html', 'emails.auth.verify-email.text'], [
                'verifyUrl' => $verifyUrl,
                'recipientEmail' => $notifiable->getEmailForVerification(),
            ]);
    }
}
