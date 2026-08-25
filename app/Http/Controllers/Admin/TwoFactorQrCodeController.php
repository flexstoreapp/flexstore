<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\TwoFactorConfirmationNotPendingException;
use App\Models\User;
use App\TwoFactor\TwoFactor;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class TwoFactorQrCodeController
{
    public function __construct(
        private TwoFactor $twoFactor
    ) {
    }

    public function show(#[CurrentUser] User $user): JsonResponse
    {
        $user->refresh();

        throw_unless($user->hasPendingTwoFactorConfirmation(), TwoFactorConfirmationNotPendingException::class);

        return response()->json([
            'svg' => $this->twoFactor->getQrCodeSvg($user),
        ]);
    }
}
