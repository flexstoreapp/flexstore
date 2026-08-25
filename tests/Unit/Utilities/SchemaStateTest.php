<?php

declare(strict_types=1);

use App\Utilities\SchemaState;

covers(SchemaState::class);

uses()->group('utilities');

beforeEach(function () {
    @unlink(app(SchemaState::class)->path());
});

afterEach(function () {
    @unlink(app(SchemaState::class)->path());
});

test('is not current until the migration files have been reconciled', function () {
    $state = app(SchemaState::class);

    expect($state->isCurrent())->toBeFalse();

    $state->markCurrent();

    expect($state->isCurrent())->toBeTrue();
});

test('stops being current when a migration file is added', function () {
    $state = app(SchemaState::class);
    $state->markCurrent();

    $added = database_path('migrations/9999_01_01_000000_schema_state_probe.php');
    file_put_contents($added, '<?php');

    try {
        expect($state->isCurrent())->toBeFalse();
    } finally {
        @unlink($added);
    }

    expect($state->isCurrent())->toBeTrue();
});

test('reports a failure only while the files that caused it are in place', function () {
    $state = app(SchemaState::class);
    $state->markFailed('migration exploded');

    expect($state->failure())->toBe('migration exploded');

    $state->markCurrent();

    expect($state->failure())->toBeNull();
});

test('treats an unreadable marker as out of date rather than throwing', function () {
    $state = app(SchemaState::class);

    file_put_contents($state->path(), 'not json');

    expect($state->isCurrent())->toBeFalse()
        ->and($state->failure())->toBeNull();
});

test('finds no pending migrations on a fully migrated database', function () {
    expect(app(SchemaState::class)->hasPendingMigrations())->toBeFalse();
});

test('reports an unknown state when the migration table cannot be read', function () {
    config(['database.default' => 'not-a-configured-connection']);

    expect(app(SchemaState::class)->hasPendingMigrations())->toBeNull();
});
