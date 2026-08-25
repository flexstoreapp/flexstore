<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Models\Brand;
use App\Queries\StorefrontShopDataQuery;
use App\Utilities\StorefrontHead;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BrandProductController
{
    public function show(ProductFilterRequest $request, Brand $brand, StorefrontShopDataQuery $query): Response
    {
        if (! $brand->is_active) {
            abort(404);
        }

        StorefrontHead::brand($brand);

        return Inertia::render('storefront/shop/list', [
            ...$query->execute($request->toDto()->withContextBrand($brand->id)),
            'context' => fn (): array => [
                'type' => 'brand',
                'name' => $brand->getTranslations('name'),
                'description' => $brand->getTranslations('description'),
            ],
        ]);
    }
}
