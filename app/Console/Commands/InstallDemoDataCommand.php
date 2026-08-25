<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\InstallDemoDataAction;
use App\Installer\Contracts\InstallationState;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

#[Signature('demo:install
    {--force : Reset the database without confirmation}
    {--keep-database : Import on top of the current schema instead of running migrate:fresh}
    {--rebuild-media : Re-run every image through the media pipeline and rewrite the processed cache}')]
#[Description('Reset the database and install the demo dataset, images and storefront content.')]
final class InstallDemoDataCommand extends Command
{
    public function handle(InstallDemoDataAction $installDemoData, InstallationState $installationState): int
    {
        Artisan::call('optimize:clear');

        if (! $this->option('keep-database')) {
            if (! $this->confirmReset($installationState)) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }

            $reset = false;

            $this->components->task('Resetting the database', function () use (&$reset): bool {
                return $reset = $this->resetDatabase();
            });

            if (! $reset) {
                $this->newLine();
                $this->error(mb_trim(Artisan::output()) !== '' ? mb_trim(Artisan::output()) : 'Could not reset the database.');

                return self::FAILURE;
            }

            File::delete(storage_path('installed'));
        }

        if (! File::exists(public_path('storage'))) {
            $this->components->task('Creating the storage symlink', fn (): bool => Artisan::call('storage:link', ['--force' => true]) === 0);
        }

        try {
            $this->components->task('Installing demo data', function () use ($installDemoData): void {
                $installDemoData->handle(rebuildMediaCache: $this->option('rebuild-media') === true);
            });
        } catch (Throwable $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->task('Clearing caches', function (): void {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
        });

        if (app()->isProduction()) {
            $this->components->task('Optimizing', fn (): bool => Artisan::call('optimize') === 0);
        }

        $installationState->markAsInstalled();

        $this->newLine();
        $this->components->info('Demo data installed.');
        $this->components->twoColumnDetail('Super admin', 'admin@flexstore.app / password');
        $this->components->twoColumnDetail('Customer', 'emma.wilson@email.com / password');

        return self::SUCCESS;
    }

    private function resetDatabase(): bool
    {
        DB::prohibitDestructiveCommands(false);

        try {
            return Artisan::call('migrate:fresh', ['--force' => true]) === 0;
        } finally {
            DB::prohibitDestructiveCommands(app()->isProduction());
        }
    }

    private function confirmReset(InstallationState $installationState): bool
    {
        if ($this->option('force') || ! $installationState->isInstalled()) {
            return true;
        }

        return $this->confirm('This app is already installed. Reset the database and reinstall? This will DROP all tables.', false);
    }
}
