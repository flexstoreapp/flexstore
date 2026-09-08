<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Api\V1\Admin\SearchProductRequest;
use App\Http\Resources\Api\V1\Admin\AdminProductSearchResource;
use App\Queries\ProductSearchQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ProductSearchController
{
    public function __invoke(SearchProductRequest $request, ProductSearchQuery $query): AnonymousResourceCollection
    {
        return AdminProductSearchResource::collection(
            $query->execute($request->safe()->except('with_variants'), $request->safe()->boolean('with_variants')),
        );
    }
}
