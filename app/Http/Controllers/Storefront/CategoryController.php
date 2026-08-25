<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Queries\StorefrontCategoryListQuery;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CategoryController
{
    public function index(StorefrontCategoryListQuery $query): Response
    {
        StorefrontHead::page(__('Categories'));

        return Inertia::render('storefront/categories/list', [
            'categories' => $query->execute(),
        ]);
    }
}
