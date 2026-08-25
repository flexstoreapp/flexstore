<?php

declare(strict_types=1);

use App\Installer\EnvWriter;

covers(EnvWriter::class);

uses()->group('installer');

beforeEach(function () {
    $this->envPath = base_path('.env');
    $this->envBackup = file_get_contents($this->envPath);
});

afterEach(function () {
    file_put_contents($this->envPath, $this->envBackup);
});

test('updates an existing key', function () {
    (new EnvWriter)->write(['APP_NAME' => 'TestApp']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('APP_NAME=TestApp');
});

test('adds a new key when not found', function () {
    (new EnvWriter)->write(['BRAND_NEW_KEY' => 'brand_new_value']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('BRAND_NEW_KEY=brand_new_value');
});

test('quotes values containing spaces', function () {
    (new EnvWriter)->write(['APP_NAME' => 'My App']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('APP_NAME="My App"');
});

test('quotes and escapes values containing double quotes', function () {
    (new EnvWriter)->write(['APP_NAME' => 'My "App" Name']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('APP_NAME="My \\"App\\" Name"');
});

test('quotes and escapes values containing backslashes', function () {
    (new EnvWriter)->write(['APP_NAME' => 'path\\to\\file']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('APP_NAME="path\\\\to\\\\file"');
});

test('quotes values containing hash', function () {
    (new EnvWriter)->write(['FLEXSTORE_TEST_HASH' => 'pass#word']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('FLEXSTORE_TEST_HASH="pass#word"');
});

test('does not quote simple values', function () {
    (new EnvWriter)->write(['APP_ENV' => 'production']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('APP_ENV=production')
        ->and($env)->not->toContain('APP_ENV="production"');
});

test('uncomments commented-out keys', function () {
    $env = file_get_contents($this->envPath);
    file_put_contents($this->envPath, $env . "\n# COMMENTED_KEY=old_value");

    (new EnvWriter)->write(['COMMENTED_KEY' => 'new_value']);

    $env = file_get_contents($this->envPath);

    expect($env)
        ->toContain('COMMENTED_KEY=new_value')
        ->not->toContain('# COMMENTED_KEY=');
});

test('writes multiple values at once', function () {
    (new EnvWriter)->write([
        'APP_NAME' => 'MultiTest',
        'APP_ENV' => 'staging',
    ]);

    $env = file_get_contents($this->envPath);

    expect($env)
        ->toContain('APP_NAME=MultiTest')
        ->toContain('APP_ENV=staging');
});

test('wraps empty values in quotes', function () {
    (new EnvWriter)->write(['FLEXSTORE_TEST_EMPTY' => '']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('FLEXSTORE_TEST_EMPTY=""');
});

test('does not duplicate keys when called multiple times', function () {
    (new EnvWriter)->write(['NEW_KEY' => 'first']);
    (new EnvWriter)->write(['NEW_KEY' => 'second']);

    $env = file_get_contents($this->envPath);

    expect($env)->toContain('NEW_KEY=second')
        ->and(mb_substr_count($env, 'NEW_KEY='))->toBe(1);
});

test('does not duplicate keys when writing same key in one call', function () {
    (new EnvWriter)->write(['ANOTHER_KEY' => 'value1']);

    $env = file_get_contents($this->envPath);

    expect(mb_substr_count($env, 'ANOTHER_KEY='))->toBe(1);

    (new EnvWriter)->write(['ANOTHER_KEY' => 'value2']);

    $env = file_get_contents($this->envPath);

    expect(mb_substr_count($env, 'ANOTHER_KEY='))->toBe(1)
        ->and($env)->toContain('ANOTHER_KEY=value2');
});

test('syncs written values into the live process environment', function () {
    $key = 'FLEXSTORE_ENV_SYNC_' . mb_strtoupper(uniqid());

    try {
        (new EnvWriter)->write([$key => 'synced-value']);

        expect($_ENV[$key] ?? null)->toBe('synced-value')
            ->and($_SERVER[$key] ?? null)->toBe('synced-value')
            ->and(getenv($key))->toBe('synced-value');
    } finally {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }
});

test('process environment sync overwrites previously set values', function () {
    $key = 'FLEXSTORE_ENV_SYNC_' . mb_strtoupper(uniqid());
    $_ENV[$key] = 'stale';
    $_SERVER[$key] = 'stale';
    putenv("{$key}=stale");

    try {
        (new EnvWriter)->write([$key => 'fresh']);

        expect($_ENV[$key])->toBe('fresh')
            ->and($_SERVER[$key])->toBe('fresh')
            ->and(getenv($key))->toBe('fresh');
    } finally {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }
});
