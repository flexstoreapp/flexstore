<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateUserAction;
use App\Http\Requests\UpdateCustomerPasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PasswordController
{
    public function edit(): Response
    {
        return Inertia::render('admin/account/password');
    }

    public function update(UpdateCustomerPasswordRequest $request, #[CurrentUser] User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle($user->refresh(), $request->toDto());

        return back();
    }
}
