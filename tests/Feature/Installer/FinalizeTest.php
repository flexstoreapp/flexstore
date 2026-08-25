<?php

declare(strict_types=1);

use App\Http\Controllers\Installer\FinalizeController;
use App\Installer\Contracts\EnvWriter;
use App\Installer\Contracts\InstallationState;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(FinalizeController::class, App\Http\Requests\Installer\StoreFinalizeRequest::class);

uses()->group('installer');

function mockInstallationState(
    bool $isInstalled = false,
    bool $databaseIsMigrated = true,
    bool $licenseIsActive = true,
    ?string $expectOnce = null,
): Mockery\MockInterface {
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn($isInstalled);
    $mock->shouldReceive('databaseIsMigrated')->andReturn($databaseIsMigrated);
    $mock->shouldReceive('licenseIsActive')->andReturn($licenseIsActive);

    if ($expectOnce === 'markAsInstalled') {
        $mock->shouldReceive('markAsInstalled')->once();
        $mock->shouldReceive('removeInstallerKey');
    } elseif ($expectOnce === 'removeInstallerKey') {
        $mock->shouldReceive('markAsInstalled');
        $mock->shouldReceive('removeInstallerKey')->once();
    } else {
        $mock->shouldReceive('markAsInstalled');
        $mock->shouldReceive('removeInstallerKey');
    }

    app()->instance(InstallationState::class, $mock);

    return $mock;
}

beforeEach(function () {
    Schema::hasTable('settings');

    mockInstallationState();

    $envWriter = Mockery::mock(EnvWriter::class);
    $envWriter->shouldReceive('write');
    app()->instance(EnvWriter::class, $envWriter);

    Artisan::shouldReceive('call')->zeroOrMoreTimes();
});

test('finalize page can be rendered', function () {
    get(route('installer.finalize.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('installer/finalize')
            ->has('appUrl')
            ->has('timezones')
        );
});

test('store marks application as installed and sends the installer to the done page', function () {
    mockInstallationState(expectOnce: 'markAsInstalled');

    post(route('installer.finalize.store'), ['store_name' => 'My Store', 'timezone' => 'UTC'])
        ->assertRedirect(url('/install/done'));
});

test('store forces a full page visit to the done page for inertia requests', function () {
    mockInstallationState();

    post(route('installer.finalize.store'), ['store_name' => 'My Store', 'timezone' => 'UTC'], ['X-Inertia' => 'true'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', '/install/done');
});

test('store calls removeInstallerKey', function () {
    mockInstallationState(expectOnce: 'removeInstallerKey');

    post(route('installer.finalize.store'), ['store_name' => 'My Store', 'timezone' => 'UTC']);
});

test('store saves store name to settings', function () {
    post(route('installer.finalize.store'), ['store_name' => 'My Store', 'timezone' => 'UTC']);

    expect(Setting::getValue('store_name'))->toBe('My Store');
});

test('store name is required', function () {
    post(route('installer.finalize.store'), ['timezone' => 'UTC'])
        ->assertSessionHasErrors('store_name');
});

test('timezone is required', function () {
    post(route('installer.finalize.store'), ['store_name' => 'My Store'])
        ->assertSessionHasErrors('timezone');
});

test('timezone must be a valid timezone identifier', function () {
    post(route('installer.finalize.store'), ['store_name' => 'My Store', 'timezone' => 'Invalid/Timezone'])
        ->assertSessionHasErrors('timezone');
});

test('finalize page redirects to home when already installed', function () {
    mockInstallationState(isInstalled: true);

    get(route('installer.finalize.show'))->assertRedirect('/');
});

test('finalize page redirects to database step when database is not migrated', function () {
    mockInstallationState(databaseIsMigrated: false);

    get(route('installer.finalize.show'))
        ->assertRedirect(route('installer.database.create'));
});

test('finalize store redirects to database step when database is not migrated', function () {
    mockInstallationState(databaseIsMigrated: false);

    post(route('installer.finalize.store'), ['store_name' => 'My Store', 'timezone' => 'UTC'])
        ->assertRedirect(route('installer.database.create'));
});

test('store switches session and cache drivers to database', function () {
    $envWriter = Mockery::mock(EnvWriter::class);
    $envWriter->shouldReceive('write')
        ->once()
        ->with(Mockery::on(fn (array $values): bool => ($values['SESSION_DRIVER'] ?? null) === 'database'
            && ($values['CACHE_STORE'] ?? null) === 'database'
            && ($values['APP_ENV'] ?? null) === 'production'
            && ($values['APP_DEBUG'] ?? null) === 'false'));
    app()->instance(EnvWriter::class, $envWriter);

    post(route('installer.finalize.store'), ['store_name' => 'My Store', 'timezone' => 'UTC'])
        ->assertRedirect();
});
