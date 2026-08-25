<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TwoFactorNotEnabledException;
use App\Models\User;
use App\TwoFactor\RecoveryCode;

final readonly class RegenerateRecoveryCodesAction
{
    public function __construct(
        private RecoveryCode $recoveryCode
    ) {
    }

    /**
     * @throws TwoFactorNotEnabledException
     */
    public function handle(User $user): User
    {
        throw_unless($user->hasTwoFactorEnabled(), TwoFactorNotEnabledException::class);

        $user->update([
            'two_factor_recovery_codes' => $this->recoveryCode->generate(),
        ]);

        return $user;
    }
}
