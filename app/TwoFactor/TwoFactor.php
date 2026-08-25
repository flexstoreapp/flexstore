<?php

declare(strict_types=1);

namespace App\TwoFactor;

use App\Models\User;

final readonly class TwoFactor
{
    public function __construct(private Totp $totp)
    {
    }

    public function getQrCodeSvg(User $user): string
    {
        if (! $user->two_factor_secret) {
            return '';
        }

        return $this->totp->generateQrCodeSvg($user, $user->two_factor_secret);
    }

    public function getManualEntryKey(User $user): string
    {
        if (! $user->two_factor_secret) {
            return '';
        }

        return mb_rtrim(chunk_split((string) $user->two_factor_secret, 4, ' '));
    }
}
