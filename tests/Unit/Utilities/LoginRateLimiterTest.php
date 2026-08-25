<?php

declare(strict_types=1);

use App\Utilities\LoginRateLimiter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

covers(LoginRateLimiter::class);

uses()->group('utilities', 'auth');

function makeLoginRequest(string $ip = '127.0.0.1'): Request
{
    return Request::create('/login', 'POST', server: ['REMOTE_ADDR' => $ip]);
}

afterEach(fn () => Cache::flush());

test('passes silently when no attempts have been recorded', function () {
    expect(fn () => app(LoginRateLimiter::class)->ensureNotRateLimited('user@example.com', makeLoginRequest()))
        ->not->toThrow(ValidationException::class);
});

test('passes silently while under the configured max attempts', function () {
    $limiter = app(LoginRateLimiter::class);
    $request = makeLoginRequest();

    foreach (range(1, config('auth.login_max_attempts') - 1) as $_) {
        $limiter->hit('user@example.com', $request);
    }

    expect(fn () => $limiter->ensureNotRateLimited('user@example.com', $request))
        ->not->toThrow(ValidationException::class);
});

test('throws auth.throttle and dispatches Lockout when the max is reached', function () {
    Event::fake(Lockout::class);

    $limiter = app(LoginRateLimiter::class);
    $request = makeLoginRequest();

    foreach (range(1, config('auth.login_max_attempts')) as $_) {
        $limiter->hit('user@example.com', $request);
    }

    expect(fn () => $limiter->ensureNotRateLimited('user@example.com', $request))
        ->toThrow(ValidationException::class);

    Event::assertDispatched(Lockout::class);
});

test('clear() resets the counter so a previously locked-out caller is allowed again', function () {
    $limiter = app(LoginRateLimiter::class);
    $request = makeLoginRequest();

    foreach (range(1, config('auth.login_max_attempts')) as $_) {
        $limiter->hit('user@example.com', $request);
    }

    $limiter->clear('user@example.com', $request);

    expect(fn () => $limiter->ensureNotRateLimited('user@example.com', $request))
        ->not->toThrow(ValidationException::class);
});

test('counters are scoped per email so one user does not lock out another at the same IP', function () {
    $limiter = app(LoginRateLimiter::class);
    $request = makeLoginRequest('10.0.0.1');

    foreach (range(1, config('auth.login_max_attempts')) as $_) {
        $limiter->hit('victim@example.com', $request);
    }

    expect(fn () => $limiter->ensureNotRateLimited('bystander@example.com', $request))
        ->not->toThrow(ValidationException::class);
});

test('counters are scoped per IP so the same email from a different IP is independent', function () {
    $limiter = app(LoginRateLimiter::class);

    foreach (range(1, config('auth.login_max_attempts')) as $_) {
        $limiter->hit('user@example.com', makeLoginRequest('10.0.0.1'));
    }

    expect(fn () => $limiter->ensureNotRateLimited('user@example.com', makeLoginRequest('10.0.0.2')))
        ->not->toThrow(ValidationException::class);
});

test('email key is case-insensitive so MIXED-case input shares the counter with lowercase', function () {
    $limiter = app(LoginRateLimiter::class);
    $request = makeLoginRequest();

    foreach (range(1, config('auth.login_max_attempts')) as $_) {
        $limiter->hit('USER@Example.com', $request);
    }

    expect(fn () => $limiter->ensureNotRateLimited('user@example.com', $request))
        ->toThrow(ValidationException::class);
});

test('missing request IP degrades gracefully to an empty ip segment in the key', function () {
    $limiter = app(LoginRateLimiter::class);
    $request = Request::create('/login', 'POST'); // no REMOTE_ADDR

    foreach (range(1, config('auth.login_max_attempts')) as $_) {
        $limiter->hit('user@example.com', $request);
    }

    expect(fn () => $limiter->ensureNotRateLimited('user@example.com', $request))
        ->toThrow(ValidationException::class);
});

test('throws with the auth.throttle translation message including seconds and minutes', function () {
    $limiter = app(LoginRateLimiter::class);
    $request = makeLoginRequest();

    foreach (range(1, config('auth.login_max_attempts')) as $_) {
        $limiter->hit('user@example.com', $request);
    }

    try {
        $limiter->ensureNotRateLimited('user@example.com', $request);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        $message = $e->errors()['email'][0] ?? '';
        $expectedSeconds = RateLimiter::availableIn('user@example.com|127.0.0.1');
        $expected = __('auth.throttle', [
            'seconds' => $expectedSeconds,
            'minutes' => ceil($expectedSeconds / 60),
        ]);

        expect($message)->toBe($expected);
    }
});
