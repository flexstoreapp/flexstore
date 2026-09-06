<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ClearCartAction;
use App\Actions\ResolveVisitorCartAction;
use App\Enums\DisplayTaxTotals;
use App\Models\Setting;
use App\Queries\CartCrossSellsQuery;
use App\Utilities\CartCookie;
use App\Utilities\StorefrontHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CartController
{
    private const int CROSS_SELL_LIMIT = 4;

    public function show(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        CartCrossSellsQuery $crossSellsQuery,
    ): Response {
        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        StorefrontHead::page(__('Shopping cart'));

        return Inertia::render('storefront/cart/show', [
            'pricesIncludeTax' => (bool) Setting::getValue('prices_include_tax'),
            'displayTaxTotals' => (DisplayTaxTotals::tryFrom((string) Setting::getValue('display_tax_totals'))
                ?? DisplayTaxTotals::Single)->value,
            ...Setting::getValue('storefront_cart_show_cross_sells', true) ? [
                'crossSellProducts' => Inertia::defer(fn (): array => $crossSellsQuery->execute($cart, self::CROSS_SELL_LIMIT)),
            ] : [],
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
