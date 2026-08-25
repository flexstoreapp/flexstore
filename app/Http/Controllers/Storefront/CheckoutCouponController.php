<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ApplyCouponToCartAction;
use App\Actions\RemoveCouponFromCartAction;
use App\Actions\ResolveVisitorCartAction;
use App\Http\Requests\Storefront\StoreCheckoutCouponRequest;
use App\Utilities\CartCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class CheckoutCouponController
{
    public function store(
        StoreCheckoutCouponRequest $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        ApplyCouponToCartAction $action,
    ): RedirectResponse {
        $action->handle(
            $resolveVisitorCart->handle(CartCookie::from($request), $request->user())->id,
            $request->validated('coupon_code'),
            $request->validated('customer_email'),
            $request->user(),
        );

        return back();
    }

    public function destroy(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        RemoveCouponFromCartAction $action,
    ): RedirectResponse {
        $action->handle($resolveVisitorCart->handle(CartCookie::from($request), $request->user())->id, $request->user());

        return back();
    }
}
