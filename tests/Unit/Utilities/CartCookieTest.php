<?php

declare(strict_types=1);

use App\Utilities\CartCookie;
use Illuminate\Http\Request;

covers(CartCookie::class);

uses()->group('utilities', 'cart');

test('reads the cart id from the request cookie', function () {
    $request = Request::create('/');
    $request->cookies->set(CartCookie::NAME, 'cart-123');

    expect(CartCookie::from($request))->toBe('cart-123');
});

test('returns null when the cookie is absent', function () {
    expect(CartCookie::from(Request::create('/')))->toBeNull();
});

test('returns null when the cookie is not a string', function () {
    $request = Request::create('/');
    $request->cookies->set(CartCookie::NAME, ['not', 'a', 'string']);

    expect(CartCookie::from($request))->toBeNull();
});

test('exposes the cookie name', function () {
    expect(CartCookie::NAME)->toBe('cart_id');
});
