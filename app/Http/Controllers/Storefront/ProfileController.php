<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\DeleteCustomerAccountAction;
use App\Actions\UpdateUserAction;
use App\Enums\Role;
use App\Http\Requests\Storefront\DestroyProfileRequest;
use App\Http\Requests\Storefront\UpdateAccountProfileRequest;
use App\Models\User;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProfileController
{
    public function edit(): Response
    {
        StorefrontHead::page(__('Profile'));

        return Inertia::render('storefront/account/profile');
    }

    public function update(
        UpdateAccountProfileRequest $request,
        #[CurrentUser] User $user,
        UpdateUserAction $action
    ): RedirectResponse {
        $originalEmail = $user->email;

        $action->handle($user->refresh(), $request->toDto());

        if ($user->email !== $originalEmail) {
            $user->sendEmailVerificationNotification();
        }

        return back();
    }

    public function destroy(
        DestroyProfileRequest $request,
        #[CurrentUser] User $user,
        DeleteCustomerAccountAction $action,
    ): RedirectResponse {
        abort_if($user->hasRole(Role::SuperAdmin), 403);

        Auth::guard('web')->logout();

        $action->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('account.login');
    }
}
