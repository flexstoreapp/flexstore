<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ResolveVisitorCartAction;
use App\Models\Setting;
use App\Queries\CartCrossSellsQuery;
use App\Utilities\CartCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CartCrossSellController
{
    private const int LIMIT = 3;

    public function __invoke(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        CartCrossSellsQuery $query,
    ): JsonResponse {
        if (! Setting::getValue('storefront_cart_show_cross_sells', true)) {
            return response()->json([]);
        }

        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        return response()->json($query->execute($cart, self::LIMIT));
    }
}
