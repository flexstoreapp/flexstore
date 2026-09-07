<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ClearCartAction;
use App\Actions\ResolveVisitorCartAction;
use App\Http\Resources\Api\V1\CartResource;
use App\Queries\StorefrontCartQuery;
use App\Utilities\ApiCartToken;
use Illuminate\Http\Request;

final readonly class CartController
{
    public function show(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $resolveVisitorCart->handle(ApiCartToken::from($request), $request->user());

        return new CartResource($query->execute($cart));
    }

    public function destroy(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        ClearCartAction $clearCart,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $resolveVisitorCart->handle(ApiCartToken::from($request), $request->user());

        $clearCart->handle($cart);

        return new CartResource($query->execute($cart->refresh()));
    }
}
