<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;

final readonly class RemoveWishlistItemAction
{
    public function __construct(
        private ResolveWishlistAction $resolveWishlistAction,
    ) {
    }

    public function handle(?string $wishlistId, int $productId, ?User $customer = null): Wishlist
    {
        $wishlist = $this->resolveWishlistAction->handle($wishlistId, $customer);

        WishlistItem::query()
            ->where('wishlist_id', $wishlist->id)
            ->where('product_id', $productId)
            ->delete();

        return $wishlist->load('items');
    }
}
