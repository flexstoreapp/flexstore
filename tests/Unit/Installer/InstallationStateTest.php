<?php

declare(strict_types=1);

use App\Installer\InstallationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

covers(InstallationState::class);

uses()->group('installer');

beforeEach(function () {
    $this->originalStoragePath = storage_path();
    $this->tempStorage = sys_get_temp_dir() . '/flexstore-test-' . uniqid();
    mkdir($this->tempStorage . '/framework', recursive: true);
    app()->useStoragePath($this->tempStorage);
});

afterEach(function () {
    app()->useStoragePath($this->originalStoragePath);
    @unlink($this->tempStorage . '/installed');
    @unlink($this->tempStorage . '/framework/installer_key');
    @rmdir($this->tempStorage . '/framework');
    @rmdir($this->tempStorage);
});

test('is not installed when file does not exist', function () {
    expect((new InstallationState)->isInstalled())->toBeFalse();
});

test('is installed when file exists', function () {
    file_put_contents(storage_path('installed'), 'test');

    expect((new InstallationState)->isInstalled())->toBeTrue();
});

test('mark as installed creates the file', function () {
    (new InstallationState)->markAsInstalled();

    expect(file_exists(storage_path('installed')))->toBeTrue()
        ->and(file_get_contents(storage_path('installed')))->not->toBeEmpty();
});

test('installer key returns null when file does not exist', function () {
    expect((new InstallationState)->getInstallerKey())->toBeNull();
});

test('installer key returns trimmed contents', function () {
    file_put_contents(storage_path('framework/installer_key'), "  secret-key\n");

    expect((new InstallationState)->getInstallerKey())->toBe('secret-key');
});

test('remove installer key deletes the file', function () {
    file_put_contents(storage_path('framework/installer_key'), 'secret-key');

    (new InstallationState)->removeInstallerKey();

    expect(file_exists(storage_path('framework/installer_key')))->toBeFalse();
});

test('remove installer key does not fail when file does not exist', function () {
    (new InstallationState)->removeInstallerKey();

    expect(file_exists(storage_path('framework/installer_key')))->toBeFalse();
});

test('database is migrated returns true when users table exists', function () {
    expect((new InstallationState)->databaseIsMigrated())->toBeTrue();
});

test('database is migrated returns false when users table is missing', function () {
    $driver = DB::getDriverName();

    try {
        if ($driver === 'pgsql') {
            DB::statement('DROP TABLE users CASCADE');
        } else {
            Schema::disableForeignKeyConstraints();
            Schema::drop('users');
            Schema::enableForeignKeyConstraints();
        }

        expect((new InstallationState)->databaseIsMigrated())->toBeFalse();
    } finally {
        // SQLite rolls DDL back via the test transaction; other drivers commit it implicitly.
        if ($driver !== 'sqlite') {
            Illuminate\Support\Facades\Artisan::call('migrate:fresh');
        }
    }
});

test('database is migrated returns false when the connection itself fails', function () {
    $driver = DB::getDriverName();
    $original = config("database.connections.{$driver}");

    try {
        if ($driver === 'sqlite') {
            config(['database.connections.sqlite.database' => '/nonexistent/' . uniqid() . '.sqlite']);
        } else {
            config(["database.connections.{$driver}.host" => '127.0.0.1']);
            config(["database.connections.{$driver}.port" => '1']);
        }

        DB::purge($driver);

        expect((new InstallationState)->databaseIsMigrated())->toBeFalse();
    } finally {
        config(["database.connections.{$driver}" => $original]);
        DB::purge($driver);
    }
});
