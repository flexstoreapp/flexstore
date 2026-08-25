<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreSessionRequest;
use App\Models\User;
use App\Utilities\LoginRateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SessionController
{
    public function create(Request $request): Response
    {
        return Inertia::render('admin/auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(StoreSessionRequest $request, LoginRateLimiter $rateLimiter): RedirectResponse
    {
        $email = $request->safe()->string('email')->value();
        $password = $request->safe()->string('password')->value();
        $remember = $request->safe()->boolean('remember');

        $rateLimiter->ensureNotRateLimited($email, $request);

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $rateLimiter->hit($email, $request);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            if (! Auth::guard('web')->validate(['email' => $email, 'password' => $password])) {
                $rateLimiter->hit($email, $request);

                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }

            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $remember);

            return to_route('admin.two-factor.challenge');
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $rateLimiter->hit($email, $request);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $rateLimiter->clear($email, $request);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.login');
    }
}
