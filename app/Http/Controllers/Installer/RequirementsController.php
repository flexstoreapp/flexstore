<?php

declare(strict_types=1);

namespace App\Http\Controllers\Installer;

use App\Installer\Contracts\EnvWriter;
use App\Installer\Contracts\InstallationState;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RequirementsController
{
    public function show(): Response
    {
        $php = $this->checkPhp();
        $extensions = $this->checkExtensions();
        $drivers = $this->checkDatabaseDrivers();
        $directories = $this->checkDirectories();
        $envWritable = is_writable(base_path('.env'));

        $canProceed = $php['passed']
            && $envWritable
            && collect($extensions)->every('passed')
            && collect($drivers)->contains('passed', true)
            && collect($directories)->every('passed');

        return Inertia::render('installer/requirements', [
            'php' => $php,
            'extensions' => $extensions,
            'drivers' => $drivers,
            'directories' => $directories,
            'envWritable' => $envWritable,
            'canProceed' => $canProceed,
        ]);
    }

    public function store(EnvWriter $environmentWriter, InstallationState $installationState): RedirectResponse
    {
        $key = $installationState->getInstallerKey() ?? 'base64:' . base64_encode(random_bytes(32));

        $environmentWriter->write(['APP_KEY' => $key]);

        config(['app.key' => $key]);

        $installationState->removeInstallerKey();

        return to_route('installer.database.create');
    }

    /**
     * @return array{required: string, current: string, passed: bool}
     */
    private function checkPhp(): array
    {
        return [
            'required' => '8.4.0',
            'current' => PHP_VERSION,
            'passed' => version_compare(PHP_VERSION, '8.4.0', '>='),
        ];
    }

    /**
     * @return array<int, array{name: string, passed: bool}>
     */
    private function checkExtensions(): array
    {
        $required = [
            'bcmath',
            'ctype',
            'curl',
            'dom',
            'fileinfo',
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
            'zip',
        ];

        return array_map(fn (string $ext): array => [
            'name' => $ext,
            'passed' => extension_loaded($ext),
        ], $required);
    }

    /**
     * @return array<int, array{name: string, label: string, passed: bool}>
     */
    private function checkDatabaseDrivers(): array
    {
        return [
            ['name' => 'pdo_mysql', 'label' => 'MySQL / MariaDB', 'passed' => extension_loaded('pdo_mysql')],
            ['name' => 'pdo_pgsql', 'label' => 'PostgreSQL', 'passed' => extension_loaded('pdo_pgsql')],
            ['name' => 'pdo_sqlite', 'label' => 'SQLite', 'passed' => extension_loaded('pdo_sqlite')],
        ];
    }

    /**
     * @return array<int, array{path: string, passed: bool}>
     */
    private function checkDirectories(): array
    {
        $directories = [
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ];

        return array_map(fn (string $dir): array => [
            'path' => $dir,
            'passed' => is_writable(base_path($dir)),
        ], $directories);
    }
}
