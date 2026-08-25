<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\RegenerateRecoveryCodesAction;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class TwoFactorRecoveryCodesController
{
    public function store(#[CurrentUser] User $user, RegenerateRecoveryCodesAction $action): RedirectResponse
    {
        $action->handle($user->refresh());

        return back();
    }
}
