<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\User;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EmailVerificationNoticeController
{
    public function __invoke(#[CurrentUser] User $user): Response|RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('account.dashboard', absolute: false));
        }

        StorefrontHead::page(__('Verify your email'));

        return Inertia::render('storefront/account/auth/verify-email');
    }
}
