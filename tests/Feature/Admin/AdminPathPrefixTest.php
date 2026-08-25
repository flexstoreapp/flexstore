<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Utilities\AdminPath;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

covers(AdminPath::class);

uses()->group('admin', 'admin-path');

function serveAdminFrom(string $prefix): void
{
    config(['admin.prefix' => $prefix]);

    Route::setRoutes(new RouteCollection);
    Route::middleware('web')->group(base_path('routes/web.php'));
    Route::getRoutes()->refreshNameLookups();
}

test('admin panel is served from the default prefix', function () {
    get('/admin/login')->assertOk();
});

test('admin panel is served from a custom prefix', function () {
    serveAdminFrom('control-panel');

    get('/control-panel/login')->assertOk();
});

test('the default prefix stops resolving once a custom one is configured', function () {
    serveAdminFrom('control-panel');

    get('/admin/login')->assertNotFound();
});

test('named admin routes resolve to the custom prefix', function () {
    serveAdminFrom('control-panel');

    expect(route('admin.login', absolute: false))->toBe('/control-panel/login');
});
