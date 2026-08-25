<?php

declare(strict_types=1);

namespace App\TwoFactor;

use App\Models\User;
use Illuminate\Support\Str;
use SensitiveParameter;

final readonly class RecoveryCode
{
    /**
     * @return list<string>
     */
    public function generate(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = mb_strtoupper(Str::random(10) . '-' . Str::random(10));
        }

        return $codes;
    }

    public function validateAndConsume(User $user, #[SensitiveParameter] string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        $codeIndex = array_search($code, $recoveryCodes, true);

        if ($codeIndex === false) {
            return false;
        }

        unset($recoveryCodes[$codeIndex]);

        $user->update([
            'two_factor_recovery_codes' => array_values($recoveryCodes),
        ]);

        return true;
    }
}
