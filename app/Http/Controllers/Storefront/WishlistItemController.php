<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\AddWishlistItemAction;
use App\Actions\RemoveWishlistItemAction;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class WishlistItemController
{
    public function update(Request $request, Product $product, AddWishlistItemAction $action): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $wishlistId = $request->cookie('wishlist_id');
        $wishlist = $action->handle(is_string($wishlistId) ? $wishlistId : null, $product->id, $request->user());

        return back()->withCookie(cookie()->forever('wishlist_id', $wishlist->id));
    }

    public function destroy(Request $request, Product $product, RemoveWishlistItemAction $action): RedirectResponse
    {
        $wishlistId = $request->cookie('wishlist_id');
        $wishlist = $action->handle(is_string($wishlistId) ? $wishlistId : null, $product->id, $request->user());

        return back()->withCookie(cookie()->forever('wishlist_id', $wishlist->id));
    }
}
