<?php

declare(strict_types=1);

namespace App\Providers;

use App\Installer\Contracts\InstallationState as InstallationStateContract;
use App\Installer\InstallationState;
use Illuminate\Support\ServiceProvider;
use Override;

final class InstallerServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->scoped(InstallationStateContract::class, InstallationState::class);

        if ($this->shouldSkipInstaller()) {
            return;
        }

        $this->clearFrameworkCaches();
        $this->applyInstallerRuntimeConfig();
        $this->applyDotenvToCurrentProcess();
        $this->applyDatabaseConfigFromDotenv();
    }

    private function shouldSkipInstaller(): bool
    {
        if ($this->app->make(InstallationStateContract::class)->isInstalled()) {
            return true;
        }

        return $this->app->runningInConsole();
    }

    private function clearFrameworkCaches(): void
    {
        foreach ([
            $this->app->getCachedConfigPath(),
            $this->app->getCachedRoutesPath(),
            $this->app->getCachedEventsPath(),
        ] as $cachePath) {
            if (file_exists($cachePath)) {
                @unlink($cachePath);
            }
        }
    }

    private function applyInstallerRuntimeConfig(): void
    {
        if (empty(config('app.key'))) {
            config(['app.key' => $this->getTemporaryKey()]);
        }

        config([
            'app.env' => 'local',
            'app.debug' => true,
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);
    }

    /**
     * Re-read .env from disk and write the values into the current process's
     * environment so env() / config:cache see the latest contents.
     *
     * Laravel's phpdotenv loader is immutable + per-process, so when multiple
     * workers serve the installer (e.g., `php artisan serve` with
     * PHP_CLI_SERVER_WORKERS > 1) workers that loaded the original .env never
     * see updates the install flow writes mid-session. Forcibly overwriting
     * $_ENV / $_SERVER / getenv() here makes every installer request — and
     * any artisan sub-bootstrap it triggers, e.g., config:cache — observe the
     * current contents of .env regardless of which worker handled which step.
     */
    private function applyDotenvToCurrentProcess(): void
    {
        foreach ($this->readDotenv() as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    private function applyDatabaseConfigFromDotenv(): void
    {
        $values = $this->readDotenv();
        $connection = $values['DB_CONNECTION'] ?? null;

        if ($connection === null || $connection === '') {
            return;
        }

        config(['database.default' => $connection]);

        if ($connection === 'sqlite') {
            if (isset($values['DB_DATABASE']) && $values['DB_DATABASE'] !== '') {
                config(['database.connections.sqlite.database' => $values['DB_DATABASE']]);
            }

            return;
        }

        $map = [
            'DB_HOST' => 'host',
            'DB_PORT' => 'port',
            'DB_DATABASE' => 'database',
            'DB_USERNAME' => 'username',
            'DB_PASSWORD' => 'password',
        ];

        foreach ($map as $key => $configKey) {
            if (array_key_exists($key, $values)) {
                config(["database.connections.{$connection}.{$configKey}" => $values[$key]]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function readDotenv(): array
    {
        $envPath = base_path('.env');

        if (! is_readable($envPath)) {
            return [];
        }

        $contents = file_get_contents($envPath);

        if ($contents === false) {
            return [];
        }

        return $this->parseDotenv($contents);
    }

    /**
     * @return array<string, string>
     */
    private function parseDotenv(string $contents): array
    {
        $values = [];

        foreach (preg_split('/\r\n|\r|\n/', $contents) ?: [] as $line) {
            $line = mb_trim($line);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, '#')) {
                continue;
            }

            $position = mb_strpos($line, '=');

            if ($position === false) {
                continue;
            }

            $key = mb_trim(mb_substr($line, 0, $position));
            $value = mb_trim(mb_substr($line, $position + 1));

            if (mb_strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
                $value = stripcslashes(mb_substr($value, 1, -1));
            } elseif (mb_strlen($value) >= 2 && $value[0] === "'" && $value[-1] === "'") {
                $value = mb_substr($value, 1, -1);
            }

            $values[$key] = $value;
        }

        return $values;
    }

    private function getTemporaryKey(): string
    {
        $keyFile = storage_path('framework/installer_key');

        if (file_exists($keyFile)) {
            $contents = file_get_contents($keyFile);

            if ($contents !== false) {
                return mb_trim($contents);
            }
        }

        $key = 'base64:' . base64_encode(random_bytes(32));
        @file_put_contents($keyFile, $key, LOCK_EX);

        return $key;
    }
}
