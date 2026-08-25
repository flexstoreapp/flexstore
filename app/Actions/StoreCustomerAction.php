<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreCustomerInput;
use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminNewCustomerNotification;
use Illuminate\Support\Facades\DB;

final readonly class StoreCustomerAction
{
    public function __construct(
        private SendAdminNotificationAction $sendAdminNotificationAction,
    ) {
    }

    public function handle(StoreCustomerInput $input): User
    {
        $user = DB::transaction(function () use ($input): User {
            $user = User::query()->create([
                'name' => $input->name,
                'email' => $input->email,
                'password' => $input->password,
                'email_verified_at' => $input->emailVerifiedAt,
            ]);

            $user->assignRole(Role::Customer);

            return $user;
        });

        if (Setting::getValue('notification_admin_new_customer')) {
            $this->sendAdminNotificationAction->handle(new AdminNewCustomerNotification($user));
        }

        return $user;
    }
}
