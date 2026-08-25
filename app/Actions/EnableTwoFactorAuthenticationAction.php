<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\TwoFactor\Totp;

final readonly class EnableTwoFactorAuthenticationAction
{
    public function __construct(
        private Totp $totp
    ) {
    }

    public function handle(User $user): User
    {
        $user->update([
            'two_factor_secret' => $this->totp->generateSecret(),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return $user;
    }
}
