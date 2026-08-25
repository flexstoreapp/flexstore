<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmTwoFactorAuthenticationAction;
use App\Http\Requests\Admin\ConfirmTwoFactorRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class TwoFactorConfirmationController
{
    public function store(ConfirmTwoFactorRequest $request, #[CurrentUser] User $user, ConfirmTwoFactorAuthenticationAction $action): RedirectResponse
    {
        $action->handle($user->refresh(), $request->validated('code'));

        return back();
    }
}
