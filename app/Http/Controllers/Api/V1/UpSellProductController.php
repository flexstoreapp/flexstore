<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductRelationType;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Models\Product;
use App\Queries\ProductRelationsQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class UpSellProductController
{
    public function __invoke(Product $product, ProductRelationsQuery $query): AnonymousResourceCollection
    {
        abort_unless($product->is_active, 404);

        return ProductCardResource::collection($query->execute($product, ProductRelationType::UpSell));
    }
}
