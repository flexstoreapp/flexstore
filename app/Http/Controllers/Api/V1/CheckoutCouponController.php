<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ApplyCouponToCartAction;
use App\Actions\RemoveCouponFromCartAction;
use App\Actions\ResolveVisitorCartAction;
use App\Http\Requests\Storefront\StoreCheckoutCouponRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Queries\StorefrontCartQuery;
use App\Utilities\ApiCartToken;
use Illuminate\Http\Request;

final readonly class CheckoutCouponController
{
    public function store(
        StoreCheckoutCouponRequest $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        ApplyCouponToCartAction $action,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $resolveVisitorCart->handle(ApiCartToken::from($request), $request->user());

        $action->handle(
            $cart->id,
            $request->validated('coupon_code'),
            $request->validated('customer_email'),
            $request->user(),
        );

        return new CartResource($query->execute($cart->refresh()));
    }

    public function destroy(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        RemoveCouponFromCartAction $action,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $resolveVisitorCart->handle(ApiCartToken::from($request), $request->user());

        $action->handle($cart->id, $request->user());

        return new CartResource($query->execute($cart->refresh()));
    }
}
