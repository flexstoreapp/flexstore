<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Queries\StorefrontShopDataQuery;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShopController
{
    public function index(ProductFilterRequest $request, StorefrontShopDataQuery $query): Response
    {
        StorefrontHead::shop();

        return Inertia::render('storefront/shop/list', $query->execute($request->toDto()));
    }
}
