<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Requests\Storefront\LogoutRequest;
use App\Http\Requests\Storefront\ShowLoginRequest;
use App\Http\Requests\Storefront\StoreAccountSessionRequest;
use App\Utilities\LoginRateLimiter;
use App\Utilities\StorefrontHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SessionController
{
    public function create(ShowLoginRequest $request): Response
    {
        $redirectTo = $request->safe()->string('redirect_to')->value();

        if ($redirectTo !== '') {
            $request->session()->put('url.intended', $redirectTo);
        }

        StorefrontHead::page(__('Log in'));

        return Inertia::render('storefront/account/auth/login', [
        ]);
    }

    public function store(StoreAccountSessionRequest $request, LoginRateLimiter $rateLimiter): RedirectResponse
    {
        $email = $request->safe()->string('email')->value();

        $rateLimiter->ensureNotRateLimited($email, $request);

        $passed = Auth::attempt(
            ['email' => $email, 'password' => $request->safe()->string('password')->value()],
            $request->safe()->boolean('remember'),
        );

        if (! $passed) {
            $rateLimiter->hit($email, $request);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $rateLimiter->clear($email, $request);
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard', absolute: false));
    }

    public function destroy(LogoutRequest $request): RedirectResponse
    {
        $redirectTo = $request->safe()->string('redirect_to')->value();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($redirectTo !== '' ? $redirectTo : '/');
    }
}
