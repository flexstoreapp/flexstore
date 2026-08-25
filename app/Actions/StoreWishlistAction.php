<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Wishlist;

final readonly class StoreWishlistAction
{
    public function handle(?string $wishlistId = null, ?User $customer = null): Wishlist
    {
        $wishlist = Wishlist::query()->create([
            'id' => $wishlistId,
            'customer_id' => $customer?->id,
        ]);

        $wishlist->setRelation('items', $wishlist->items()->getModel()->newCollection());

        return $wishlist;
    }
}
