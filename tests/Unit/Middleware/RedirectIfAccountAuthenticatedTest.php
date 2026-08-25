<?php

declare(strict_types=1);

use App\Http\Middleware\RedirectIfAccountAuthenticated;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

covers(RedirectIfAccountAuthenticated::class);

uses()->group('middleware', 'auth');

test('redirects authenticated users to account dashboard', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $middleware = new RedirectIfAccountAuthenticated();

    $response = $middleware->handle(Request::create('/account/login'), function () {
        return new Response('should not pass');
    });

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('account.dashboard'));
});

test('passes request through for guests', function () {
    $middleware = new RedirectIfAccountAuthenticated();

    $response = $middleware->handle(Request::create('/account/login'), fn () => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});
