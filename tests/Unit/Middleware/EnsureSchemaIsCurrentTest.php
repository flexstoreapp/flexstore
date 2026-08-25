<?php

declare(strict_types=1);

use App\Actions\RunPendingMigrationsAction;
use App\Http\Middleware\EnsureSchemaIsCurrent;
use App\Installer\Contracts\InstallationState;
use App\Utilities\DatabaseFile;
use App\Utilities\SchemaState;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

covers(EnsureSchemaIsCurrent::class);

uses()->group('middleware');

function schemaInstallationState(bool $installed): InstallationState
{
    return new class($installed) implements InstallationState
    {
        public function __construct(private bool $installed)
        {
        }

        public function isInstalled(): bool
        {
            return $this->installed;
        }

        public function markAsInstalled(): void
        {
        }

        public function databaseIsMigrated(): bool
        {
            return true;
        }

        public function licenseIsActive(): bool
        {
            return true;
        }

        public function getInstallerKey(): ?string
        {
            return null;
        }

        public function removeInstallerKey(): void
        {
        }
    };
}

function runMiddleware(InstallationState $state, SchemaState $schemaState): HttpResponse
{
    $middleware = new EnsureSchemaIsCurrent(
        $state,
        $schemaState,
        app(DatabaseFile::class),
        app(RunPendingMigrationsAction::class),
    );

    return $middleware->handle(Request::create('/'), fn () => new Response('passed through'));
}

beforeEach(function () {
    @unlink(app(SchemaState::class)->path());
});

afterEach(function () {
    @unlink(app(SchemaState::class)->path());
});

test('passes the request through when the app is not installed', function () {
    $response = runMiddleware(schemaInstallationState(false), app(SchemaState::class));

    expect($response->getContent())->toBe('passed through');
});

test('passes the request through when the migration files are unchanged', function () {
    $schemaState = app(SchemaState::class);
    $schemaState->markCurrent();

    $response = runMiddleware(schemaInstallationState(true), $schemaState);

    expect($response->getContent())->toBe('passed through');
});

test('records the fingerprint and continues when files changed but nothing is pending', function () {
    $schemaState = app(SchemaState::class);

    expect($schemaState->isCurrent())->toBeFalse();

    $response = runMiddleware(schemaInstallationState(true), $schemaState);

    expect($response->getContent())->toBe('passed through')
        ->and($schemaState->isCurrent())->toBeTrue();
});

test('passes through without recording a fingerprint when the migration state is unreadable', function () {
    $schemaState = app(SchemaState::class);

    config(['database.default' => 'not-a-configured-connection']);

    $response = runMiddleware(schemaInstallationState(true), $schemaState);

    expect($response->getContent())->toBe('passed through')
        ->and($schemaState->isCurrent())->toBeFalse();
});

test('holds the request with a retryable response when a previous attempt failed', function () {
    $schemaState = app(SchemaState::class);
    $schemaState->markFailed('SQLSTATE[42S01]: Base table already exists');

    $response = runMiddleware(schemaInstallationState(true), $schemaState);

    expect($response->getStatusCode())->toBe(503)
        ->and($response->headers->get('Retry-After'))->toBe('10')
        ->and($response->getContent())->toContain('Update failed')
        ->and($response->getContent())->not->toContain('SQLSTATE');
});

test('stops with a restore message when the sqlite file is gone', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.driver' => 'sqlite',
        'database.connections.sqlite.database' => 'storage/definitely-not-here.sqlite',
    ]);

    $response = runMiddleware(schemaInstallationState(true), app(SchemaState::class));

    expect($response->getStatusCode())->toBe(503)
        ->and($response->getContent())->toContain('Database not found')
        ->and($response->getContent())->toContain('Restore it from your backup');
});
