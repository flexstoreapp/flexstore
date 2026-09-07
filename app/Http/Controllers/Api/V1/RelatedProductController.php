<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ProductCardResource;
use App\Models\Product;
use App\Models\Setting;
use App\Queries\RelatedProductsQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class RelatedProductController
{
    public function __invoke(Product $product, RelatedProductsQuery $query): AnonymousResourceCollection
    {
        abort_unless($product->is_active, 404);

        $limit = (int) Setting::getValue('storefront_product_detail_related_products_count', 10);

        return ProductCardResource::collection($query->execute($product, $limit));
    }
}
