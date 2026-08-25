<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[SensitiveParameter]
        public readonly string $token,
        public readonly bool $admin = false,
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
        assert($notifiable instanceof CanResetPassword);

        $expiryMinutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject(__('Reset your password'))
            ->view(['emails.auth.password-reset.html', 'emails.auth.password-reset.text'], [
                'resetUrl' => $this->resetUrl($notifiable),
                'expiryMinutes' => $expiryMinutes,
                'recipientEmail' => $notifiable->getEmailForPasswordReset(),
            ]);
    }

    private function resetUrl(CanResetPassword $notifiable): string
    {
        return route($this->admin ? 'admin.password.reset' : 'account.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
