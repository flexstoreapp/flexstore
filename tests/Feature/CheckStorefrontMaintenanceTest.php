<?php

declare(strict_types=1);

use App\Http\Middleware\CheckStorefrontMaintenance;
use App\Models\Setting;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

covers(CheckStorefrontMaintenance::class);

uses()->group('middleware');

test('allows access when maintenance mode is disabled', function () {
    Setting::query()->where('key', 'maintenance_mode')->update(['value' => false]);

    $response = get(route('home'));

    $response->assertOk();
});

test('blocks storefront access when maintenance mode is enabled', function () {
    Setting::query()->where('key', 'maintenance_mode')->update(['value' => true]);

    $response = get(route('home'));

    $response->assertStatus(503)
        ->assertInertia(
            fn (AssertableInertia $page) => $page->component('storefront/maintenance')
        );
});

test('allows access for allowed IP addresses during maintenance', function () {
    Setting::query()->where('key', 'maintenance_mode')->update(['value' => true]);
    Setting::query()->where('key', 'maintenance_allowed_ips')->update([
        'value' => json_encode(['127.0.0.1']),
    ]);

    $response = get(route('home'));

    $response->assertOk();
});

test('blocks access for non-allowed IP addresses during maintenance', function () {
    Setting::query()->where('key', 'maintenance_mode')->update(['value' => true]);
    Setting::query()->where('key', 'maintenance_allowed_ips')->update([
        'value' => json_encode(['203.0.113.1']),
    ]);

    $response = get(route('home'));

    $response->assertStatus(503)
        ->assertInertia(
            fn (AssertableInertia $page) => $page->component('storefront/maintenance')
        );
});

test('does not affect admin routes during maintenance', function () {
    Setting::query()->where('key', 'maintenance_mode')->update(['value' => true]);

    $response = get(route('admin.login'));

    $response->assertOk();
});

test('does not affect webhook routes during maintenance', function () {
    Setting::query()->where('key', 'maintenance_mode')->update(['value' => true]);

    $response = get(route('admin.login'));

    $response->assertOk();
});
