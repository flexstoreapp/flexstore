<?php

declare(strict_types=1);

namespace App\Installer;

use App\Installer\Contracts\InstallationState as InstallationStateContract;
use Illuminate\Support\Facades\Schema;
use Throwable;

final readonly class InstallationState implements InstallationStateContract
{
    public function isInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }

    public function markAsInstalled(): void
    {
        file_put_contents(storage_path('installed'), now()->toDateTimeString(), LOCK_EX);
    }

    public function databaseIsMigrated(): bool
    {
        try {
            return Schema::hasTable('users');
        } catch (Throwable) {
            return false;
        }
    }

    public function getInstallerKey(): ?string
    {
        $keyFile = storage_path('framework/installer_key');

        if (! file_exists($keyFile)) {
            return null;
        }

        $contents = file_get_contents($keyFile);

        return $contents !== false ? mb_trim($contents) : null;
    }

    public function removeInstallerKey(): void
    {
        @unlink(storage_path('framework/installer_key'));
    }
}
