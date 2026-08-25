<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

final readonly class SendCustomerNotificationAction
{
    public function handle(Notification $notification, ?User $customer = null, ?string $customerEmail = null): void
    {
        try {
            if ($customer instanceof User) {
                $customer->notify($notification);

                return;
            }

            if ($customerEmail) {
                NotificationFacade::route('mail', $customerEmail)->notify($notification);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
