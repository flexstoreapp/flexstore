<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductSearchRequest;
use App\Queries\ProductSearchQuery;
use Illuminate\Http\JsonResponse;

final readonly class ProductSearchController
{
    public function __invoke(ProductSearchRequest $request, ProductSearchQuery $query): JsonResponse
    {
        return response()->json(
            $query->execute($request->query(), $request->safe()->boolean('with_variants'))
        );
    }
}
