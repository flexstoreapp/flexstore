<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DisableTwoFactorAuthenticationAction;
use App\Actions\EnableTwoFactorAuthenticationAction;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class TwoFactorAuthenticationController
{
    public function store(#[CurrentUser] User $user, EnableTwoFactorAuthenticationAction $action): RedirectResponse
    {
        $action->handle($user);

        return back();
    }

    public function destroy(#[CurrentUser] User $user, DisableTwoFactorAuthenticationAction $action): RedirectResponse
    {
        $action->handle($user->refresh());

        return back();
    }
}
