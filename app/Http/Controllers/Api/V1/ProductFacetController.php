<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Http\Resources\Api\V1\ProductFacetsResource;
use App\Queries\StorefrontShopFacetsQuery;

final readonly class ProductFacetController
{
    public function __invoke(ProductFilterRequest $request, StorefrontShopFacetsQuery $query): ProductFacetsResource
    {
        $input = $request->toDto();

        return new ProductFacetsResource($query->execute(
            contextCategoryId: $input->contextCategoryId,
            contextBrandId: $input->contextBrandId,
            search: $input->search,
        ));
    }
}
