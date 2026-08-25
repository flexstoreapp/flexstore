<?php

declare(strict_types=1);

use App\Http\Controllers\Installer\DatabaseController;
use App\Http\Requests\Installer\StoreDatabaseRequest;
use App\Installer\Contracts\EnvWriter;
use App\Installer\Contracts\InstallationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(DatabaseController::class, StoreDatabaseRequest::class);

uses()->group('installer');

beforeEach(function () {
    $state = Mockery::mock(InstallationState::class);
    $state->shouldReceive('isInstalled')->andReturn(false);
    app()->instance(InstallationState::class, $state);

    $envWriter = Mockery::mock(EnvWriter::class);
    $envWriter->shouldReceive('write');
    app()->instance(EnvWriter::class, $envWriter);
});

test('database page can be rendered', function () {
    get(route('installer.database.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('installer/database')
            ->has('availableDrivers')
        );
});

test('db_connection is required', function () {
    post(route('installer.database.store'), [
        'db_database' => 'test_db',
    ])->assertSessionHasErrors('db_connection');
});

test('db_connection must be a valid driver', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'invalid_driver',
        'db_database' => 'test_db',
    ])->assertSessionHasErrors('db_connection');
});

test('db_host is required for non-sqlite connections', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'mysql',
        'db_port' => '3306',
        'db_database' => 'test_db',
        'db_username' => 'root',
    ])->assertSessionHasErrors('db_host');
});

test('db_port is required for non-sqlite connections', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'mysql',
        'db_host' => 'localhost',
        'db_database' => 'test_db',
        'db_username' => 'root',
    ])->assertSessionHasErrors('db_port');
});

test('db_database is required', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'sqlite',
    ])->assertSessionHasErrors('db_database');
});

test('db_username is required for non-sqlite connections', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'mysql',
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_database' => 'test_db',
    ])->assertSessionHasErrors('db_username');
});

test('db_port must be a valid port number', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'mysql',
        'db_host' => 'localhost',
        'db_port' => 'abc',
        'db_database' => 'test_db',
        'db_username' => 'root',
    ])->assertSessionHasErrors('db_port');
});

test('db_port must be within valid range', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'mysql',
        'db_host' => 'localhost',
        'db_port' => '99999',
        'db_database' => 'test_db',
        'db_username' => 'root',
    ])->assertSessionHasErrors('db_port');
});

test('sqlite db_database must be a filename without path separators', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'sqlite',
        'db_database' => '../storage/evil.sqlite',
    ])->assertSessionHasErrors('db_database');
});

test('sqlite db_database rejects absolute paths', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'sqlite',
        'db_database' => '/tmp/evil.sqlite',
    ])->assertSessionHasErrors('db_database');
});

test('demo_data must be a boolean', function () {
    post(route('installer.database.store'), [
        'db_connection' => 'sqlite',
        'db_database' => 'test_db.sqlite',
        'demo_data' => 'maybe',
    ])->assertSessionHasErrors('demo_data');
});

test('sqlite expands the filename to a project-relative path in the environment', function () {
    $filename = 'installer-test-' . bin2hex(random_bytes(4)) . '.sqlite';
    $path = storage_path($filename);
    $written = [];

    $envWriter = Mockery::mock(EnvWriter::class);
    $envWriter->shouldReceive('write')
        ->once()
        ->with(Mockery::on(function (array $values) use (&$written): bool {
            $written = $values;

            return true;
        }));
    app()->instance(EnvWriter::class, $envWriter);

    try {
        post(route('installer.database.store'), [
            'db_connection' => 'sqlite',
            'db_database' => $filename,
        ])->assertRedirect(route('installer.account.create'));

        expect($written['DB_DATABASE'] ?? null)->toBe('storage/' . $filename)
            ->and($written['DB_CONNECTION'] ?? null)->toBe('sqlite');
    } finally {
        File::delete($path);
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge();
    }
});

test('database page redirects to home when already installed', function () {
    $state = Mockery::mock(InstallationState::class);
    $state->shouldReceive('isInstalled')->andReturn(true);
    app()->instance(InstallationState::class, $state);

    get(route('installer.database.create'))->assertRedirect('/');
});
