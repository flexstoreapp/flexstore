<?php

declare(strict_types=1);

use App\Http\Middleware\Api\ForceJsonResponse;
use App\Models\Setting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(ForceJsonResponse::class);

uses()->group('api');

test('an unknown resource returns a json error, not the storefront error page', function (): void {
    $response = getJson(route('api.v1.products.show', 'no-such-product'));

    $response->assertNotFound()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message']);

    expect($response->content())->not->toContain('<!DOCTYPE html>');
});

test('an unknown api route returns a json error', function (): void {
    $response = getJson('/api/v1/does-not-exist');

    $response->assertNotFound()->assertHeader('content-type', 'application/json');

    expect($response->content())->not->toContain('<!DOCTYPE html>');
});

test('an unauthenticated request returns a json error rather than a redirect', function (): void {
    getJson(route('api.v1.account.orders.index'))
        ->assertUnauthorized()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message']);
});

test('a forbidden request returns a json error', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['something-else']);

    getJson(route('api.v1.account.profile.show'))
        ->assertForbidden()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message']);
});

test('a validation failure names the fields that failed', function (): void {
    postJson(route('api.v1.auth.login'), ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message', 'errors' => ['email', 'password', 'device_name']]);
});

test('a client asking for html on an api route is still answered with json', function (): void {
    // Not getJson(): that would set the Accept header this test exists to override.
    $response = get(route('api.v1.config'), ['Accept' => 'text/html']);

    $response->assertOk()->assertHeader('content-type', 'application/json');

    expect($response->content())->not->toContain('<!DOCTYPE html>');
});

test('maintenance mode answers api clients with json, not the storefront page', function (): void {
    Setting::setValue('maintenance_mode', true);

    $response = getJson(route('api.v1.config'))
        ->assertStatus(503)
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message']);

    expect($response->content())->not->toContain('<!DOCTYPE html>');
});

test('an unknown api path answers with json even when the client asks for html', function (): void {
    get('/api/v1/does-not-exist', ['Accept' => 'text/html'])
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['message']);
});
