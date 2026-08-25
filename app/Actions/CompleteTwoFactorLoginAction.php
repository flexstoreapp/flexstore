<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\TwoFactorChallengeInput;
use App\Models\User;
use App\TwoFactor\RecoveryCode;
use App\TwoFactor\Totp;
use Illuminate\Validation\ValidationException;

final readonly class CompleteTwoFactorLoginAction
{
    public function __construct(
        private Totp $totp,
        private RecoveryCode $recoveryCode,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function handle(User $user, TwoFactorChallengeInput $input, string $ip): void
    {
        assert($user->two_factor_secret !== null);

        $validated = false;
        if ($input->code !== null && $input->code !== '') {
            $validated = $this->totp->validateCode($user->two_factor_secret, $input->code);
        } elseif ($input->recoveryCode !== null && $input->recoveryCode !== '') {
            $validated = $this->recoveryCode->validateAndConsume($user, $input->recoveryCode);
        }

        if (! $validated) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
