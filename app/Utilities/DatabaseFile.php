<?php

declare(strict_types=1);

namespace App\Utilities;

final class DatabaseFile
{
    /**
     * True when this install runs on SQLite and the configured file is gone,
     * which is what an upgrade that replaced the database directory looks like.
     */
    public function isMissing(): bool
    {
        $path = $this->configuredPath();

        return $path !== null && ! file_exists($path);
    }

    public function configuredPath(): ?string
    {
        $connection = config('database.default');

        if (! is_string($connection) || config("database.connections.{$connection}.driver") !== 'sqlite') {
            return null;
        }

        $database = config("database.connections.{$connection}.database");

        if (! is_string($database) || $database === ':memory:' || str_starts_with($database, 'file:')) {
            return null;
        }

        if ($this->isAbsolute($database)) {
            return $database;
        }

        return base_path($database);
    }

    private function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
