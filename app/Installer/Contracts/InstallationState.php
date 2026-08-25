<?php

declare(strict_types=1);

namespace App\Installer\Contracts;

interface InstallationState
{
    public function isInstalled(): bool;

    public function markAsInstalled(): void;

    public function databaseIsMigrated(): bool;

    public function getInstallerKey(): ?string;

    public function removeInstallerKey(): void;
}
