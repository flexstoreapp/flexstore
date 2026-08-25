<?php

declare(strict_types=1);

use App\Utilities\DatabaseFile;

covers(DatabaseFile::class);

uses()->group('utilities');

test('ignores connections that are not sqlite', function () {
    config([
        'database.default' => 'mysql',
        'database.connections.mysql.driver' => 'mysql',
        'database.connections.mysql.database' => 'flexstore',
    ]);

    expect(app(DatabaseFile::class)->configuredPath())->toBeNull()
        ->and(app(DatabaseFile::class)->isMissing())->toBeFalse();
});

test('ignores in-memory and dsn databases', function (string $database) {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.driver' => 'sqlite',
        'database.connections.sqlite.database' => $database,
    ]);

    expect(app(DatabaseFile::class)->isMissing())->toBeFalse();
})->with([':memory:', 'file:store?mode=memory']);

test('resolves a relative path against the application root', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.driver' => 'sqlite',
        'database.connections.sqlite.database' => 'storage/shop.sqlite',
    ]);

    expect(app(DatabaseFile::class)->configuredPath())->toBe(base_path('storage/shop.sqlite'));
});

test('keeps an absolute path as given', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.driver' => 'sqlite',
        'database.connections.sqlite.database' => '/srv/data/shop.sqlite',
    ]);

    expect(app(DatabaseFile::class)->configuredPath())->toBe('/srv/data/shop.sqlite');
});

test('reports a missing file only when it is really gone', function () {
    $path = storage_path('database-file-probe.sqlite');

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.driver' => 'sqlite',
        'database.connections.sqlite.database' => 'storage/database-file-probe.sqlite',
    ]);

    expect(app(DatabaseFile::class)->isMissing())->toBeTrue();

    touch($path);

    try {
        expect(app(DatabaseFile::class)->isMissing())->toBeFalse();
    } finally {
        @unlink($path);
    }
});
