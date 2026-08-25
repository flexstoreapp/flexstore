<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Wishlist;

final readonly class ResolveWishlistAction
{
    public function __construct(
        private StoreWishlistAction $storeWishlistAction,
    ) {
    }

    public function handle(?string $wishlistId = null, ?User $customer = null): Wishlist
    {
        $wishlist = $this->findWishlist($customer, $wishlistId);

        if ($wishlist instanceof Wishlist) {
            if ($customer instanceof User && $wishlist->customer_id === null) {
                $wishlist->update(['customer_id' => $customer->id]);
            }

            return $wishlist;
        }

        return $this->storeWishlistAction->handle($wishlistId, $customer);
    }

    private function findWishlist(?User $customer, ?string $wishlistId): ?Wishlist
    {
        if ($customer instanceof User) {
            $wishlist = Wishlist::query()
                ->with('items')
                ->where('customer_id', $customer->id)
                ->first();

            if ($wishlist instanceof Wishlist) {
                return $wishlist;
            }
        }

        if (in_array($wishlistId, [null, '', '0'], true)) {
            return null;
        }

        return Wishlist::query()
            ->with('items')
            ->whereKey($wishlistId)
            ->first();
    }
}
