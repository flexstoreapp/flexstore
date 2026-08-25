<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\Product;
use App\Queries\ProductBuyBoxQuery;
use Illuminate\Http\JsonResponse;

final readonly class ProductQuickViewController
{
    public function show(Product $product, ProductBuyBoxQuery $query): JsonResponse
    {
        abort_unless($product->is_active, 404);

        return response()->json($query->execute($product));
    }
}
