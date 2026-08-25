<?php

declare(strict_types=1);

use App\Http\Controllers\Installer\RequirementsController;
use App\Installer\Contracts\EnvWriter;
use App\Installer\Contracts\InstallationState;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(RequirementsController::class);

uses()->group('installer');

function mockRequirementsInstallationState(
    bool $isInstalled = false,
    ?string $installerKey = null,
    bool $expectRemoveOnce = false,
): void {
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn($isInstalled);
    $mock->shouldReceive('getInstallerKey')->andReturn($installerKey);

    if ($expectRemoveOnce) {
        $mock->shouldReceive('removeInstallerKey')->once();
    } else {
        $mock->shouldReceive('removeInstallerKey');
    }

    app()->instance(InstallationState::class, $mock);
}

beforeEach(function () {
    mockRequirementsInstallationState();

    $envWriter = Mockery::mock(EnvWriter::class);
    $envWriter->shouldReceive('write');
    app()->instance(EnvWriter::class, $envWriter);
});

test('requirements page can be rendered', function () {
    get(route('installer.requirements.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('installer/requirements')
            ->has('php')
            ->has('extensions')
            ->has('drivers')
            ->has('directories')
            ->has('envWritable')
            ->has('canProceed')
        );
});

test('requirements check includes correct php version info', function () {
    get(route('installer.requirements.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('php.required', '8.4.0')
            ->where('php.current', PHP_VERSION)
            ->where('php.passed', true)
        );
});

test('store writes app key and redirects to database step', function () {
    post(route('installer.requirements.store'))
        ->assertRedirect(route('installer.database.create'));
});

test('store uses existing installer key if present', function () {
    $key = 'base64:' . base64_encode(random_bytes(32));

    mockRequirementsInstallationState(installerKey: $key);

    post(route('installer.requirements.store'));

    expect(config('app.key'))->toBe($key);
});

test('store calls removeInstallerKey after writing', function () {
    mockRequirementsInstallationState(expectRemoveOnce: true);

    post(route('installer.requirements.store'));
});

test('installer routes redirect to home when already installed', function () {
    mockRequirementsInstallationState(isInstalled: true);

    get(route('installer.requirements.show'))->assertRedirect('/');
});
