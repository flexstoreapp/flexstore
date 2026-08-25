<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\RunPendingMigrationsAction;
use App\Installer\Contracts\InstallationState;
use App\Utilities\DatabaseFile;
use App\Utilities\SchemaState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureSchemaIsCurrent
{
    public function __construct(
        private InstallationState $installationState,
        private SchemaState $schemaState,
        private DatabaseFile $databaseFile,
        private RunPendingMigrationsAction $runPendingMigrations,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->installationState->isInstalled()) {
            return $next($request);
        }

        if ($this->databaseFile->isMissing()) {
            return $this->pause($request, state: 'database-missing');
        }

        if ($this->schemaState->isCurrent()) {
            return $next($request);
        }

        if ($this->schemaState->failure() !== null) {
            return $this->pause($request, state: 'upgrade-failed');
        }

        $pending = $this->schemaState->hasPendingMigrations();

        // Unknown means the database could not be read; recording the fingerprint
        // now would skip the migration for good.
        if ($pending === null) {
            return $next($request);
        }

        if (! $pending) {
            $this->schemaState->markCurrent();

            return $next($request);
        }

        if (app()->runningUnitTests()) {
            return $next($request);
        }

        return $this->pause(
            $request,
            state: $this->runPendingMigrations->handle() ? 'upgrading' : 'upgrade-failed',
        );
    }

    // A 503 keeps payment gateways retrying instead of dropping the event.
    private function pause(Request $request, string $state): Response
    {
        $this->applyLocale($request);

        return response()
            ->view('upgrading', ['state' => $state], Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '10');
    }

    // SetLocale is appended to the group, so it never runs before this middleware.
    private function applyLocale(Request $request): void
    {
        $locale = $request->cookie('locale');

        if (! is_string($locale) || preg_match('/^[A-Za-z0-9_-]{2,10}$/', $locale) !== 1) {
            return;
        }

        if (file_exists(lang_path("common/{$locale}.json"))) {
            app()->setLocale($locale);
        }
    }
}
