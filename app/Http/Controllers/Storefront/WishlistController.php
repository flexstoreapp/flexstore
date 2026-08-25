<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ResolveWishlistAction;
use App\Enums\ProductSource;
use App\Models\Wishlist;
use App\Queries\SectionProductsQuery;
use App\Queries\StorefrontWishlistQuery;
use App\Utilities\StorefrontHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WishlistController
{
    public function show(
        Request $request,
        ResolveWishlistAction $resolveWishlist,
        StorefrontWishlistQuery $wishlistProductIds,
        SectionProductsQuery $products,
        ?Wishlist $wishlist = null,
    ): Response|RedirectResponse {
        $cookieWishlistId = $request->cookie('wishlist_id');
        $wishlistId = is_string($cookieWishlistId) ? $cookieWishlistId : null;

        $ownWishlist = $resolveWishlist->handle($wishlistId, $request->user());

        if (! $wishlist instanceof Wishlist) {
            return to_route('wishlist.show', $ownWishlist)
                ->withCookie(cookie()->forever('wishlist_id', $ownWishlist->id));
        }

        $isShared = ! $wishlist->is($ownWishlist);
        $visibleWishlist = $isShared ? $wishlist : $ownWishlist;

        StorefrontHead::page($isShared ? __('Shared wishlist') : __('Wishlist'));

        return Inertia::render('storefront/wishlist/show', [
            'products' => $products->execute([
                'product_source' => ProductSource::Featured->value,
                'product_ids' => $wishlistProductIds->execute($visibleWishlist)['product_ids'],
            ]),
            'is_shared' => $isShared,
            'share_url' => route('wishlist.show', $visibleWishlist),
        ]);
    }
}
