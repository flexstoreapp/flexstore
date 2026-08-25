<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\CompleteTwoFactorLoginAction;
use App\Http\Requests\Admin\TwoFactorChallengeRequest;
use App\Models\User;
use App\Utilities\LoginRateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TwoFactorChallengeController
{
    public function show(Request $request): Response
    {
        $user = User::query()->findOrFail($request->session()->get('login.id'));
        assert($user instanceof User);

        abort_unless($user->hasTwoFactorEnabled(), 404);

        return Inertia::render('admin/auth/two-factor-challenge');
    }

    public function store(
        TwoFactorChallengeRequest $request,
        CompleteTwoFactorLoginAction $action,
        LoginRateLimiter $rateLimiter,
    ): RedirectResponse {
        $user = User::query()->findOrFail($request->session()->get('login.id'));
        assert($user instanceof User);

        abort_unless($user->hasTwoFactorEnabled(), 404);

        $action->handle($user, $request->toDto(), $request->ip() ?? '');

        Auth::guard('web')->login($user, (bool) $request->session()->get('login.remember', false));

        $rateLimiter->clear($user->email, $request);

        $request->session()->forget('login');
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }
}
