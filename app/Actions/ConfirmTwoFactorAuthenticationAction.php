<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TwoFactorConfirmationNotPendingException;
use App\Exceptions\TwoFactorNotEnabledException;
use App\Exceptions\TwoFactorSetupNotStartedException;
use App\Models\User;
use App\TwoFactor\RecoveryCode;
use App\TwoFactor\Totp;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

final readonly class ConfirmTwoFactorAuthenticationAction
{
    public function __construct(
        private Totp $totp,
        private RecoveryCode $recoveryCode
    ) {
    }

    /**
     * @throws TwoFactorNotEnabledException
     * @throws TwoFactorConfirmationNotPendingException
     */
    public function handle(User $user, #[SensitiveParameter] string $code): void
    {
        throw_unless($user->hasTwoFactorSecret(), TwoFactorSetupNotStartedException::class);
        throw_unless($user->hasPendingTwoFactorConfirmation(), TwoFactorConfirmationNotPendingException::class);

        assert($user->two_factor_secret !== null);

        if (! $this->totp->validateCode($user->two_factor_secret, $code)) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ]);
        }

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->recoveryCode->generate(),
        ]);
    }
}
