<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

final readonly class RebuildCacheController
{
    public function __invoke(): RedirectResponse
    {
        Artisan::call('optimize:clear');
        Artisan::call('optimize');

        return back();
    }
}
