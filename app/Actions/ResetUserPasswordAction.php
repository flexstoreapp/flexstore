<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\PasswordResetInput;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final readonly class ResetUserPasswordAction
{
    public function handle(PasswordResetInput $input): string
    {
        return Password::reset($input->toCredentials(), function (User $user) use ($input): void {
            $user->forceFill([
                'password' => $input->password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });
    }
}
