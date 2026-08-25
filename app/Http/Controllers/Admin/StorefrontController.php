<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontController
{
    public function index(): Response
    {
        return Inertia::render('admin/storefront/builder');
    }
}
