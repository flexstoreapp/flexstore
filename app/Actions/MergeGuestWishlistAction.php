<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\DB;

final readonly class MergeGuestWishlistAction
{
    public function handle(?string $guestWishlistId, User $customer): void
    {
        if ($guestWishlistId === null || $guestWishlistId === '') {
            return;
        }

        DB::transaction(function () use ($guestWishlistId, $customer): void {
            $guestWishlist = Wishlist::query()
                ->whereKey($guestWishlistId)
                ->whereNull('customer_id')
                ->first();

            if (! $guestWishlist instanceof Wishlist) {
                return;
            }

            $customerWishlist = Wishlist::query()
                ->where('customer_id', $customer->id)
                ->first();

            if (! $customerWishlist instanceof Wishlist) {
                $guestWishlist->update(['customer_id' => $customer->id]);

                return;
            }

            $now = now();
            $rows = $guestWishlist->items()
                ->pluck('product_id')
                ->map(fn (int $productId): array => [
                    'wishlist_id' => $customerWishlist->id,
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if ($rows !== []) {
                WishlistItem::query()->insertOrIgnore($rows);
            }

            $guestWishlist->delete();
        });
    }
}
