<?php

declare(strict_types=1);

namespace App\Http\Controllers\Installer;

use App\Installer\Contracts\InstallationState;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CompletionController
{
    public function __construct(private InstallationState $installationState)
    {
    }

    public function show(): Response|RedirectResponse
    {
        if (! $this->installationState->isInstalled()) {
            return to_route('installer.requirements.show');
        }

        return Inertia::render('installer/done', [
            'storefrontUrl' => url('/'),
            'adminUrl' => route('admin.login'),
        ]);
    }
}
