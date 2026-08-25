<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Installer\Contracts\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureInstalled
{
    public function __construct(private InstallationState $installationState)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->runningUnitTests() && ! $this->installationState->isInstalled()) {
            return to_route('installer.requirements.show');
        }

        return $next($request);
    }
}
