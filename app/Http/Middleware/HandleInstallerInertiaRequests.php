<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Override;

final class HandleInstallerInertiaRequests extends Middleware
{
    protected $rootView = 'installer';

    #[Override]
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name', 'FlexStore'),
        ];
    }
}
