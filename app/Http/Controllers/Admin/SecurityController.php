<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Queries\UserPasskeyListQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SecurityController
{
    public function show(#[CurrentUser] User $user, UserPasskeyListQuery $passkeys): Response
    {
        $user->refresh();

        return Inertia::render('admin/account/security', [
            'requiresConfirmation' => $user->hasPendingTwoFactorConfirmation(),
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'recoveryCodes' => $user->two_factor_recovery_codes ?? [],
            'passkeys' => $passkeys->execute($user),
        ]);
    }
}
