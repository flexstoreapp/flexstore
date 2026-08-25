<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class LoginRateLimiter
{
    /**
     * @throws ValidationException
     */
    public function ensureNotRateLimited(string $email, Request $request): void
    {
        $throttleKey = $this->throttleKey($email, $request->ip() ?? '');

        if (! RateLimiter::tooManyAttempts($throttleKey, (int) config('auth.login_max_attempts'))) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function hit(string $email, Request $request): void
    {
        RateLimiter::hit($this->throttleKey($email, $request->ip() ?? ''));
    }

    public function clear(string $email, Request $request): void
    {
        RateLimiter::clear($this->throttleKey($email, $request->ip() ?? ''));
    }

    private function throttleKey(string $email, string $ip): string
    {
        return Str::transliterate(Str::lower($email) . '|' . $ip);
    }
}
