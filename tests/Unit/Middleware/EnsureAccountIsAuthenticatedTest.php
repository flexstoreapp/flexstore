<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAccountIsAuthenticated;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

covers(EnsureAccountIsAuthenticated::class);

uses()->group('middleware', 'auth');

test('redirects guests to the login page', function () {
    $middleware = new EnsureAccountIsAuthenticated();

    $response = $middleware->handle(Request::create('/account/dashboard'), function () {
        return new Response('should not pass');
    });

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('account.login'));
});

test('passes request through when user is authenticated', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $middleware = new EnsureAccountIsAuthenticated();

    $response = $middleware->handle(Request::create('/account/dashboard'), fn () => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});
