<?php

declare(strict_types=1);

use App\Installer\Contracts\InstallationState;
use App\Models\Setting;
use App\Providers\MailConfigServiceProvider;

covers(MailConfigServiceProvider::class);

uses()->group('providers', 'setting');

function fakeInstallation(bool $isInstalled, bool $databaseIsMigrated = true): void
{
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn($isInstalled);
    $mock->shouldReceive('databaseIsMigrated')->andReturn($databaseIsMigrated);
    app()->instance(InstallationState::class, $mock);
}

beforeEach(function () {
    config([
        'mail.default' => 'log',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 2525,
        'mail.mailers.smtp.encryption' => null,
        'mail.mailers.smtp.username' => null,
        'mail.mailers.smtp.password' => null,
        'mail.from.address' => 'env@example.com',
        'mail.from.name' => 'Env Sender',
    ]);

    fakeInstallation(isInstalled: true);
});

test('overrides mail config from saved settings', function () {
    Setting::setValue('mail_host', 'smtp.example.com');
    Setting::setValue('mail_port', 465);
    Setting::setValue('mail_encryption', 'ssl');
    Setting::setValue('mail_username', 'user@example.com');
    Setting::setValue('mail_password', 'super-secret');
    Setting::setValue('mail_from_address', 'hello@example.com');
    Setting::setValue('mail_from_name', 'My Store');

    app()->register(MailConfigServiceProvider::class, force: true);

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.com');
    expect(config('mail.mailers.smtp.port'))->toBe(465);
    expect(config('mail.mailers.smtp.encryption'))->toBe('ssl');
    expect(config('mail.mailers.smtp.username'))->toBe('user@example.com');
    expect(config('mail.mailers.smtp.password'))->toBe('super-secret');
    expect(config('mail.from.address'))->toBe('hello@example.com');
    expect(config('mail.from.name'))->toBe('My Store');
});

test('blank settings fall back to env defaults', function () {
    Setting::setValue('mail_host', null);
    Setting::setValue('mail_port', null);
    Setting::setValue('mail_from_address', null);
    Setting::setValue('mail_from_name', null);

    app()->register(MailConfigServiceProvider::class, force: true);

    expect(config('mail.default'))->toBe('log');
    expect(config('mail.mailers.smtp.host'))->toBe('127.0.0.1');
    expect(config('mail.mailers.smtp.port'))->toBe(2525);
    expect(config('mail.from.address'))->toBe('env@example.com');
    expect(config('mail.from.name'))->toBe('Env Sender');
});

test('partial overrides leave untouched values at env defaults', function () {
    Setting::setValue('mail_host', 'smtp.partial.com');
    Setting::setValue('mail_from_name', 'Partial Sender');

    app()->register(MailConfigServiceProvider::class, force: true);

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.partial.com');
    expect(config('mail.mailers.smtp.port'))->toBe(2525);
    expect(config('mail.from.address'))->toBe('env@example.com');
    expect(config('mail.from.name'))->toBe('Partial Sender');
});

test('mail.default stays unchanged when host is blank', function () {
    Setting::setValue('mail_from_address', 'only-from@example.com');

    app()->register(MailConfigServiceProvider::class, force: true);

    expect(config('mail.default'))->toBe('log');
    expect(config('mail.from.address'))->toBe('only-from@example.com');
});

test('skips override when app is not installed', function () {
    Setting::setValue('mail_host', 'smtp.example.com');
    Setting::setValue('mail_from_address', 'hello@example.com');

    fakeInstallation(isInstalled: false);

    app()->register(MailConfigServiceProvider::class, force: true);

    expect(config('mail.default'))->toBe('log');
    expect(config('mail.mailers.smtp.host'))->toBe('127.0.0.1');
    expect(config('mail.from.address'))->toBe('env@example.com');
});

test('skips override when database is not migrated', function () {
    fakeInstallation(isInstalled: true, databaseIsMigrated: false);

    app()->register(MailConfigServiceProvider::class, force: true);

    expect(config('mail.default'))->toBe('log');
    expect(config('mail.mailers.smtp.host'))->toBe('127.0.0.1');
});
