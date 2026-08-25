<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Setting;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

final readonly class SendAdminNotificationAction
{
    public function handle(Notification $notification): void
    {
        $storeEmail = Setting::getValue('store_email');

        if (! $storeEmail) {
            return;
        }

        try {
            NotificationFacade::route('mail', $storeEmail)->notify($notification);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
