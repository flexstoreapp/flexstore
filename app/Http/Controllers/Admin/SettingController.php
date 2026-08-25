<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;

final readonly class SettingController
{
    public function index(): Response
    {
        return Inertia::render('admin/settings/list');
    }
}
