<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ApplyCheckoutOptionsAction;
use App\Actions\ResolveVisitorCartAction;
use App\Http\Requests\Storefront\StoreCheckoutOptionRequest;
use App\Utilities\CartCookie;
use Illuminate\Http\RedirectResponse;

final readonly class CheckoutOptionController
{
    public function store(
        StoreCheckoutOptionRequest $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        ApplyCheckoutOptionsAction $applyCheckoutOptions,
    ): RedirectResponse {
        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        $applyCheckoutOptions->handle(
            cart: $cart,
            input: $request->toDto(),
            user: $request->user(),
            currencyCode: $request->attributes->get('active_currency'),
        );

        return back();
    }
}
