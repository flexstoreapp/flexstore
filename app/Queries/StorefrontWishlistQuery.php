<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Wishlist;

final readonly class StorefrontWishlistQuery
{
    /**
     * @return array{id: string, product_ids: array<int, int>}
     */
    public function execute(Wishlist $wishlist): array
    {
        return [
            'id' => $wishlist->id,
            'product_ids' => $wishlist->items->pluck('product_id')->all(),
        ];
    }
}
