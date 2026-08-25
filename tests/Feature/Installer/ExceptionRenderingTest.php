<?php

declare(strict_types=1);

use App\Installer\Contracts\InstallationState;

use function Pest\Laravel\get;

uses()->group('installer');

test('404 during install does not render the storefront-themed error page', function () {
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn(false);
    $mock->shouldReceive('databaseIsMigrated')->andReturn(false);
    app()->instance(InstallationState::class, $mock);

    get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertDontSee('storefront_appearance');
});

test('404 after install renders the storefront-themed Inertia error page', function () {
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn(true);
    app()->instance(InstallationState::class, $mock);

    get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('storefront/error-page'));
});
