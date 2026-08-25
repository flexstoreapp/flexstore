<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateUserAction;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProfileController
{
    public function edit(): Response
    {
        return Inertia::render('admin/account/profile');
    }

    public function update(
        UpdateProfileRequest $request,
        #[CurrentUser] User $user,
        UpdateUserAction $action,
    ): RedirectResponse {
        $action->handle($user->refresh(), $request->toDto());

        return to_route('admin.profile.edit');
    }
}
