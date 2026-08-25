<?php

declare(strict_types=1);

use App\Actions\RunPendingMigrationsAction;
use App\Utilities\SchemaState;
use Illuminate\Support\Facades\Artisan;

covers(RunPendingMigrationsAction::class);

uses()->group('actions');

beforeEach(function () {
    $state = app(SchemaState::class);

    @unlink($state->path());
    @unlink($state->path() . '.lock');
});

afterEach(function () {
    $state = app(SchemaState::class);

    @unlink($state->path());
    @unlink($state->path() . '.lock');
});

test('clears stale caches before migrating', function () {
    $called = [];

    Artisan::swap(new class($called) extends Illuminate\Console\Application
    {
        public function __construct(public array &$called)
        {
        }

        public function call($command, array $parameters = [], $outputBuffer = null): int
        {
            $this->called[] = $command;

            return 0;
        }
    });

    app(RunPendingMigrationsAction::class)->handle();

    expect($called)->toBe(['optimize:clear', 'migrate', 'optimize']);
});

test('records the fingerprint once the migration succeeds', function () {
    Artisan::swap(new class() extends Illuminate\Console\Application
    {
        public function __construct()
        {
        }

        public function call($command, array $parameters = [], $outputBuffer = null): int
        {
            return 0;
        }
    });

    $state = app(SchemaState::class);

    expect($state->isCurrent())->toBeFalse();

    $succeeded = app(RunPendingMigrationsAction::class)->handle();

    expect($succeeded)->toBeTrue()
        ->and($state->isCurrent())->toBeTrue()
        ->and($state->failure())->toBeNull();
});

test('records the error and stays out of date when the migration fails', function () {
    Artisan::swap(new class() extends Illuminate\Console\Application
    {
        public function __construct()
        {
        }

        public function call($command, array $parameters = [], $outputBuffer = null): int
        {
            if ($command === 'migrate') {
                throw new RuntimeException('migration exploded');
            }

            return 0;
        }
    });

    $state = app(SchemaState::class);

    $succeeded = app(RunPendingMigrationsAction::class)->handle();

    expect($succeeded)->toBeFalse()
        ->and($state->isCurrent())->toBeFalse()
        ->and($state->failure())->toBe('migration exploded');
});

test('yields to the request that already holds the lock', function () {
    Artisan::swap(new class() extends Illuminate\Console\Application
    {
        public function __construct()
        {
        }

        public function call($command, array $parameters = [], $outputBuffer = null): int
        {
            throw new RuntimeException('the lock holder should have prevented this');
        }
    });

    $state = app(SchemaState::class);

    $held = fopen($state->path() . '.lock', 'c');

    if ($held === false) {
        throw new RuntimeException('could not open the lock file');
    }

    flock($held, LOCK_EX);

    try {
        expect(app(RunPendingMigrationsAction::class)->handle())->toBeTrue()
            ->and($state->isCurrent())->toBeFalse();
    } finally {
        flock($held, LOCK_UN);
        fclose($held);
    }
});
