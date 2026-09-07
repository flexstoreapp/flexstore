<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Storefront\ProductFilterRequest;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Queries\ProductDetailQuery;
use App\Queries\StorefrontProductListQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ProductController
{
    public function index(ProductFilterRequest $request, StorefrontProductListQuery $query): AnonymousResourceCollection
    {
        return ProductCardResource::collection($query->execute($request->toDto()));
    }

    public function show(Product $product, ProductDetailQuery $query): ProductResource
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($query->execute($product));
    }
}
