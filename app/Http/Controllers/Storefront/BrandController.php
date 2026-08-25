<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Queries\StorefrontBrandListQuery;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BrandController
{
    public function index(StorefrontBrandListQuery $brands): Response
    {
        StorefrontHead::page(__('Brands'));

        return Inertia::render('storefront/brands/list', [
            'brands' => $brands->execute(),
        ]);
    }
}
