<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;

final readonly class AddWishlistItemAction
{
    public function __construct(
        private ResolveWishlistAction $resolveWishlistAction,
    ) {
    }

    public function handle(?string $wishlistId, int $productId, ?User $customer = null): Wishlist
    {
        $wishlist = $this->resolveWishlistAction->handle($wishlistId, $customer);

        WishlistItem::query()->firstOrCreate([
            'wishlist_id' => $wishlist->id,
            'product_id' => $productId,
        ]);

        return $wishlist->load('items');
    }
}
