<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Models\Category;
use App\Queries\StorefrontProductListQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryProductController
{
    public function index(
        ProductFilterRequest $request,
        Category $category,
        StorefrontProductListQuery $query,
    ): AnonymousResourceCollection {
        abort_unless($category->is_active, 404);

        return ProductCardResource::collection(
            $query->execute($request->toDto()->withContextCategory($category->id)),
        );
    }
}
