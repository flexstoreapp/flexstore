<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Queries\StorefrontHomepageDataQuery;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class HomepageController
{
    public function show(StorefrontHomepageDataQuery $query): Response
    {
        StorefrontHead::homepage();

        return Inertia::render('storefront/homepage', [
            'sections' => $query->execute(...),
        ]);
    }
}
