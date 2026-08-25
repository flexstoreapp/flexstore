<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\PasswordResetLinkInput;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Password;

final readonly class SendPasswordResetLinkAction
{
    public function handle(PasswordResetLinkInput $input, bool $admin = false): string
    {
        if (! $admin) {
            return Password::sendResetLink(['email' => $input->email]);
        }

        return Password::sendResetLink(
            ['email' => $input->email],
            fn (User $user, string $token) => $user->notify(new ResetPasswordNotification($token, admin: true)),
        );
    }
}
