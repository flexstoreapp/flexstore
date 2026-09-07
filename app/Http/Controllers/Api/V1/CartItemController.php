<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\DestroyCartItemAction;
use App\Actions\StoreCartItemAction;
use App\Actions\UpdateCartItemAction;
use App\Http\Requests\Api\V1\StoreCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Queries\StorefrontCartQuery;
use App\Utilities\ApiCartToken;
use Illuminate\Http\Request;

final readonly class CartItemController
{
    public function store(
        StoreCartItemRequest $request,
        StoreCartItemAction $action,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $action->handle(ApiCartToken::from($request), $request->toDto(), $request->user());

        return new CartResource($query->execute($cart));
    }

    public function update(
        UpdateCartItemRequest $request,
        int $cartItem,
        UpdateCartItemAction $action,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $action->handle(
            ApiCartToken::from($request),
            $cartItem,
            $request->safe()->integer('quantity'),
            $request->user(),
        );

        return new CartResource($query->execute($cart));
    }

    public function destroy(
        Request $request,
        int $cartItem,
        DestroyCartItemAction $action,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $action->handle(ApiCartToken::from($request), $cartItem, $request->user());

        return new CartResource($query->execute($cart));
    }
}
