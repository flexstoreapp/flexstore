<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Queries\StorefrontShopDataQuery;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SearchController
{
    public function __invoke(ProductFilterRequest $request, StorefrontShopDataQuery $query): Response
    {
        $data = $query->execute($request->toDto());

        $searchQuery = $request->safe()->string('query')->toString();

        StorefrontHead::search($searchQuery);

        return Inertia::render('storefront/shop/list', [
            ...$data,
            'searchQuery' => $searchQuery,
        ]);
    }
}
