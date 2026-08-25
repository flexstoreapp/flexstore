<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ClearCartAction;
use App\Actions\ResolveVisitorCartAction;
use App\Enums\DisplayTaxTotals;
use App\Models\Setting;
use App\Utilities\CartCookie;
use App\Utilities\StorefrontHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CartController
{
    public function show(Request $request, ResolveVisitorCartAction $resolveVisitorCart): Response
    {
        $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        StorefrontHead::page(__('Shopping cart'));

        return Inertia::render('storefront/cart/show', [
            'pricesIncludeTax' => (bool) Setting::getValue('prices_include_tax'),
            'displayTaxTotals' => (DisplayTaxTotals::tryFrom((string) Setting::getValue('display_tax_totals'))
                ?? DisplayTaxTotals::Single)->value,
        ]);
    }

    public function destroy(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        ClearCartAction $clearCart,
    ): RedirectResponse {
        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        $clearCart->handle($cart);

        return back()->withCookie(cookie()->forever(CartCookie::NAME, $cart->id));
    }
}
