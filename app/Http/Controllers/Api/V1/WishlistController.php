<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResolveWishlistAction;
use App\Enums\ProductSource;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Models\User;
use App\Queries\SectionProductsQuery;
use App\Queries\StorefrontWishlistQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class WishlistController
{
    private const int MAX_PRODUCTS = 100;

    public function show(
        #[CurrentUser] User $user,
        ResolveWishlistAction $resolveWishlist,
        StorefrontWishlistQuery $wishlistProductIds,
        SectionProductsQuery $products,
    ): AnonymousResourceCollection {
        $wishlist = $resolveWishlist->handle(null, $user);

        return ProductCardResource::collection($products->execute([
            'product_source' => ProductSource::Featured->value,
            'product_ids' => array_slice($wishlistProductIds->execute($wishlist)['product_ids'], 0, self::MAX_PRODUCTS),
        ]));
    }
}
