<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\DestroyCartItemAction;
use App\Actions\StoreCartItemAction;
use App\Actions\UpdateCartItemAction;
use App\Http\Requests\Storefront\StoreCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class CartItemController
{
    public function store(
        StoreCartItemRequest $request,
        StoreCartItemAction $action,
    ): RedirectResponse {
        $cartId = $request->cookie('cart_id');
        $cart = $action->handle(
            is_string($cartId) ? $cartId : null,
            $request->toDto(),
            $request->user(),
        );

        $cookie = cookie()->forever('cart_id', $cart->id);

        $redirect = $request->safe()->boolean('buy_now')
            ? to_route('checkout.create')
            : back();

        return $redirect->withCookie($cookie);
    }

    public function update(
        UpdateCartItemRequest $request,
        int $cartItem,
        UpdateCartItemAction $action,
    ): RedirectResponse {
        $cartId = $request->cookie('cart_id');
        $cart = $action->handle(
            is_string($cartId) ? $cartId : null,
            $cartItem,
            $request->safe()->integer('quantity'),
            $request->user(),
        );

        $cookie = cookie()->forever('cart_id', $cart->id);

        return back()->withCookie($cookie);
    }

    public function destroy(
        Request $request,
        int $cartItem,
        DestroyCartItemAction $action,
    ): RedirectResponse {
        $cartId = $request->cookie('cart_id');
        $cart = $action->handle(
            is_string($cartId) ? $cartId : null,
            $cartItem,
            $request->user(),
        );

        $cookie = cookie()->forever('cart_id', $cart->id);

        return back()->withCookie($cookie);
    }
}
