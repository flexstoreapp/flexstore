<?php

declare(strict_types=1);

namespace App\Actions;

use App\Notifications\TestMailNotification;
use Illuminate\Support\Facades\Notification;

final readonly class SendTestMailAction
{
    public function handle(string $recipient): void
    {
        Notification::route('mail', $recipient)->notify(new TestMailNotification());
    }
}
