<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Facades\ParallelTesting;
use Throwable;

final class SchemaState
{
    private const string MARKER = 'framework/schema-state.json';

    public function isCurrent(): bool
    {
        return $this->marker()['fingerprint'] === $this->fingerprint();
    }

    public function failure(): ?string
    {
        $marker = $this->marker();

        if ($marker['failed_fingerprint'] !== $this->fingerprint()) {
            return null;
        }

        return $marker['error'];
    }

    /**
     * Null when the migration state could not be read, which must never be
     * mistaken for "nothing is pending".
     */
    public function hasPendingMigrations(): ?bool
    {
        try {
            $migrator = resolve('migrator');

            $files = $migrator->getMigrationFiles(
                array_merge([database_path('migrations')], $migrator->paths()),
            );

            return array_diff(array_keys($files), $migrator->getRepository()->getRan()) !== [];
        } catch (Throwable) {
            return null;
        }
    }

    public function markCurrent(): void
    {
        $this->write(['fingerprint' => $this->fingerprint()]);
    }

    public function markFailed(string $error): void
    {
        $this->write([
            'failed_fingerprint' => $this->fingerprint(),
            'error' => $error,
        ]);
    }

    public function path(): string
    {
        $file = self::MARKER;

        if (app()->runningUnitTests()) {
            $file = str_replace('framework/', 'framework/testing/', $file);
        }

        $token = ParallelTesting::token();

        return storage_path($token === false ? $file : $file . ".{$token}");
    }

    private function fingerprint(): string
    {
        $files = glob(database_path('migrations/*.php')) ?: [];

        sort($files);

        return hash('xxh128', implode('|', array_map(basename(...), $files)));
    }

    /**
     * @return array{fingerprint: ?string, failed_fingerprint: ?string, error: ?string}
     */
    private function marker(): array
    {
        $defaults = ['fingerprint' => null, 'failed_fingerprint' => null, 'error' => null];

        $path = $this->path();

        if (! file_exists($path)) {
            return $defaults;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return $defaults;
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return $defaults;
        }

        return [
            'fingerprint' => is_string($decoded['fingerprint'] ?? null) ? $decoded['fingerprint'] : null,
            'failed_fingerprint' => is_string($decoded['failed_fingerprint'] ?? null) ? $decoded['failed_fingerprint'] : null,
            'error' => is_string($decoded['error'] ?? null) ? $decoded['error'] : null,
        ];
    }

    /**
     * @param  array<string, string>  $state
     */
    private function write(array $state): void
    {
        file_put_contents($this->path(), (string) json_encode($state), LOCK_EX);
    }
}
