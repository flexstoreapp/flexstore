<?php

declare(strict_types=1);

use App\Http\Middleware\Api\ForceJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

covers(ForceJsonResponse::class);

uses()->group('middleware', 'api');

test('a client that asks for html still gets json', function (): void {
    $request = Request::create('/api/v1/products');
    $request->headers->set('Accept', 'text/html');

    (new ForceJsonResponse())->handle($request, fn (): Response => new Response());

    expect($request->expectsJson())->toBeTrue()
        ->and($request->headers->get('Accept'))->toBe('application/json');
});

test('a client that sends no accept header gets json', function (): void {
    $request = Request::create('/api/v1/products');
    $request->headers->remove('Accept');

    (new ForceJsonResponse())->handle($request, fn (): Response => new Response());

    expect($request->expectsJson())->toBeTrue();
});

test('it passes the request down the pipeline', function (): void {
    $response = (new ForceJsonResponse())->handle(
        Request::create('/api/v1/products'),
        fn (Request $request): Response => new Response($request->getPathInfo()),
    );

    expect($response->getContent())->toBe('/api/v1/products');
});
