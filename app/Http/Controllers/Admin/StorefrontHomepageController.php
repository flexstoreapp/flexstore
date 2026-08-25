<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Queries\StorefrontSectionListQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StorefrontHomepageController
{
    public function edit(StorefrontSectionListQuery $query): Response
    {
        return Inertia::render('admin/storefront/homepage', [
            'sections' => $query->execute(),
        ]);
    }
}
