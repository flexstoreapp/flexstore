<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\IndexInventoryRequest;
use App\Http\Requests\Admin\ShowInventoryRequest;
use App\Http\Resources\Api\V1\Admin\InventoryProductResource;
use App\Http\Resources\Api\V1\Admin\InventoryVariantResource;
use App\Http\Resources\Api\V1\Admin\StockMovementResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\InventoryListQuery;
use App\Queries\StockMovementListQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class InventoryController
{
    public function index(IndexInventoryRequest $request, InventoryListQuery $query): AnonymousResourceCollection
    {
        return InventoryProductResource::collection(
            $query->execute(
                $request->safe()->only(['query', 'low_stock', 'in_stock']),
                $request->safe()->integer('per_page', 15),
            ),
        );
    }

    public function show(
        ShowInventoryRequest $request,
        Product $product,
        StockMovementListQuery $stockMovementQuery,
    ): AnonymousResourceCollection {
        $product->load(['variants.media', 'mediaGallery']);

        $variantId = $request->safe()->string('variant')->value();
        $variant = $variantId !== ''
            ? $product->variants->firstWhere('id', $variantId)
            : null;

        return StockMovementResource::collection(
            $stockMovementQuery->execute($variant ?? $product, $request->safe()->integer('per_page', 15)),
        )->additional([
            'product' => new InventoryProductResource($product),
            'variant' => $variant instanceof ProductVariant ? new InventoryVariantResource($variant) : null,
        ]);
    }
}
