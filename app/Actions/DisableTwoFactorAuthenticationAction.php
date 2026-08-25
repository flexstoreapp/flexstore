<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TwoFactorNotEnabledException;
use App\Models\User;

final readonly class DisableTwoFactorAuthenticationAction
{
    /**
     * @throws TwoFactorNotEnabledException
     */
    public function handle(User $user): User
    {
        throw_unless($user->hasTwoFactorEnabled(), TwoFactorNotEnabledException::class);

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return $user;
    }
}
