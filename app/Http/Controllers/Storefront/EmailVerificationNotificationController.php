<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class EmailVerificationNotificationController
{
    public function __invoke(#[CurrentUser] User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('account.dashboard', absolute: false));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('message', __('A new verification link has been sent to your email address.'));
    }
}
