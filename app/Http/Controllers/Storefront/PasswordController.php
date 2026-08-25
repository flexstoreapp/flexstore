<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\UpdateUserAction;
use App\Http\Requests\UpdateCustomerPasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class PasswordController
{
    public function update(
        UpdateCustomerPasswordRequest $request,
        #[CurrentUser] User $user,
        UpdateUserAction $action,
    ): RedirectResponse {
        $action->handle($user, $request->toDto());

        return back();
    }
}
