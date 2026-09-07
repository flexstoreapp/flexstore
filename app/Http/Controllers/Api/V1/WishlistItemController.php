<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AddWishlistItemAction;
use App\Actions\RemoveWishlistItemAction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class WishlistItemController
{
    public function update(Product $product, #[CurrentUser] User $user, AddWishlistItemAction $action): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $action->handle(null, $product->id, $user);

        return response()->json(['message' => __('Added to your wishlist.')]);
    }

    public function destroy(Product $product, #[CurrentUser] User $user, RemoveWishlistItemAction $action): JsonResponse
    {
        $action->handle(null, $product->id, $user);

        return response()->json(['message' => __('Removed from your wishlist.')]);
    }
}
