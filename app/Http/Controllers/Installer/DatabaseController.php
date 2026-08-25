<?php

declare(strict_types=1);

namespace App\Http\Controllers\Installer;

use App\Actions\InstallDemoDataAction;
use App\Http\Requests\Installer\StoreDatabaseRequest;
use App\Installer\Contracts\EnvWriter;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use PDO;
use Throwable;

final readonly class DatabaseController
{
    public function create(): Response
    {
        return Inertia::render('installer/database', [
            'availableDrivers' => $this->getAvailableDrivers(),
        ]);
    }

    public function store(StoreDatabaseRequest $request, EnvWriter $environmentWriter): RedirectResponse
    {
        @set_time_limit(0);
        ignore_user_abort(true);

        $validated = $request->validated();
        $installDemoData = $request->safe()->boolean('demo_data');
        unset($validated['demo_data']);

        $connection = $validated['db_connection'];

        if ($connection !== 'sqlite' && ! extension_loaded('pdo_' . $connection)) {
            return back()->withErrors(['db_connection' => "The pdo_{$connection} PHP extension is required but not installed."]);
        }

        $envPath = base_path('.env');
        $originalEnv = file_exists($envPath) ? file_get_contents($envPath) : null;

        try {
            if ($connection !== 'sqlite') {
                $validated['db_connection'] = $this->testConnection($validated);
            } else {
                // storage/ is the one directory an upgrade overlay preserves, so the
                // database file has to live there rather than beside the migrations.
                $path = storage_path($validated['db_database']);

                if (! file_exists($path) && ! @touch($path)) {
                    return back()->withErrors(['db_database' => "Could not create SQLite file at {$path}. Check that the storage directory is writable."]);
                }

                $validated['db_database'] = 'storage/' . $validated['db_database'];
            }

            $this->applyDatabaseConfig($validated);

            $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

            if ($exitCode !== 0) {
                $output = mb_substr(mb_trim(Artisan::output()), 0, 1000);

                return back()->withErrors([
                    'db_connection' => "Migration exited with code {$exitCode}." . ($output !== '' ? "\n\n{$output}" : ''),
                ]);
            }

            resolve(ConnectionResolverInterface::class)->purge();

            if (! Schema::hasTable('users')) {
                return back()->withErrors([
                    'db_connection' => 'Migration completed but the users table is not visible after re-opening the connection.',
                ]);
            }

            $this->updateEnvironment($validated, $environmentWriter);

            if ($installDemoData) {
                resolve(InstallDemoDataAction::class)->handle(forLiveStore: true);
            }

            $configCache = app()->getCachedConfigPath();

            if (file_exists($configCache)) {
                @unlink($configCache);
            }
        } catch (Throwable $e) {
            report($e);

            if ($originalEnv !== null) {
                @file_put_contents($envPath, $originalEnv, LOCK_EX);
            }

            return back()->withErrors([
                'db_connection' => $e->getMessage() !== '' ? $e->getMessage() : $e::class,
            ]);
        }

        return to_route('installer.account.create');
    }

    /**
     * @return array<int, array{value: string, label: string, defaultPort: string}>
     */
    private function getAvailableDrivers(): array
    {
        $drivers = [
            ['value' => 'mysql', 'label' => 'MySQL / MariaDB', 'defaultPort' => '3306', 'extension' => 'pdo_mysql'],
            ['value' => 'pgsql', 'label' => 'PostgreSQL', 'defaultPort' => '5432', 'extension' => 'pdo_pgsql'],
            ['value' => 'sqlite', 'label' => 'SQLite', 'defaultPort' => '', 'extension' => 'pdo_sqlite'],
        ];

        return array_values(array_map(
            fn (array $d): array => ['value' => $d['value'], 'label' => $d['label'], 'defaultPort' => $d['defaultPort']],
            array_filter($drivers, fn (array $d): bool => extension_loaded($d['extension'])),
        ));
    }

    /**
     * @param  array<string, string>  $config
     */
    private function testConnection(array $config): string
    {
        $driver = $config['db_connection'];

        $dsn = match ($driver) {
            'mysql' => sprintf('mysql:host=%s;port=%s;dbname=%s', $config['db_host'], $config['db_port'], $config['db_database']),
            'pgsql' => sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['db_host'], $config['db_port'], $config['db_database']),
            default => throw new InvalidArgumentException("Unsupported database driver: {$driver}"),
        };

        $pdo = new PDO($dsn, $config['db_username'], $config['db_password'] ?? '');

        if ($driver === 'mysql' && mb_stripos((string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION), 'mariadb') !== false) {
            return 'mariadb';
        }

        return $driver;
    }

    /**
     * @param  array<string, string>  $config
     */
    private function updateEnvironment(array $config, EnvWriter $environmentWriter): void
    {
        $connection = $config['db_connection'];

        $values = [
            'DB_CONNECTION' => $connection,
        ];

        if ($connection === 'sqlite') {
            $values['DB_DATABASE'] = $config['db_database'];
            $values['DB_HOST'] = '';
            $values['DB_PORT'] = '';
            $values['DB_USERNAME'] = '';
            $values['DB_PASSWORD'] = '';
        } else {
            $values['DB_HOST'] = $config['db_host'];
            $values['DB_PORT'] = $config['db_port'];
            $values['DB_DATABASE'] = $config['db_database'];
            $values['DB_USERNAME'] = $config['db_username'];
            $values['DB_PASSWORD'] = $config['db_password'] ?? '';
        }

        $environmentWriter->write($values);
    }

    /**
     * @param  array<string, string>  $config
     */
    private function applyDatabaseConfig(array $config): void
    {
        $connection = $config['db_connection'];

        config(['database.default' => $connection]);

        if ($connection === 'sqlite') {
            config([
                'database.connections.sqlite.database' => $config['db_database'],
            ]);
        } else {
            config([
                "database.connections.{$connection}.host" => $config['db_host'],
                "database.connections.{$connection}.port" => $config['db_port'],
                "database.connections.{$connection}.database" => $config['db_database'],
                "database.connections.{$connection}.username" => $config['db_username'],
                "database.connections.{$connection}.password" => $config['db_password'] ?? '',
            ]);
        }

        resolve(ConnectionResolverInterface::class)->purge();
    }
}
