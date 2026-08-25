<?php

declare(strict_types=1);

namespace App\Actions;

use App\Utilities\SchemaState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final readonly class RunPendingMigrationsAction
{
    public function __construct(private SchemaState $schemaState)
    {
    }

    /**
     * Returns false only when the migration itself failed. A request that cannot
     * take the lock returns true, because another request is already migrating.
     */
    public function handle(): bool
    {
        $handle = fopen($this->lockPath(), 'c');

        if ($handle === false) {
            Log::error('Could not open the migration lock file, so pending migrations cannot run.', [
                'path' => $this->lockPath(),
            ]);

            return true;
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return true;
        }

        @set_time_limit(0);
        ignore_user_abort(true);

        try {
            try {
                // Cached config and routes predate the new files, so they must go first.
                Artisan::call('optimize:clear');

                $exitCode = Artisan::call('migrate', ['--force' => true]);

                if ($exitCode !== 0) {
                    throw new RuntimeException("The migrate command exited with code {$exitCode}.");
                }

                $this->schemaState->markCurrent();
            } catch (Throwable $e) {
                Log::error('Automatic migration failed after a file update.', ['exception' => $e]);

                $this->schemaState->markFailed($e->getMessage());

                return false;
            }

            // The schema is already current, so a failed cache warm must not undo that.
            try {
                Artisan::call('optimize');
            } catch (Throwable $e) {
                Log::warning('Rebuilding the framework caches failed after an automatic migration.', ['exception' => $e]);
            }

            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function lockPath(): string
    {
        return $this->schemaState->path() . '.lock';
    }
}
