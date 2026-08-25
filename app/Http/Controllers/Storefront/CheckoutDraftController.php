<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ResolveVisitorCartAction;
use App\Actions\RevalidateCartCouponAction;
use App\Actions\UpdatePendingCheckoutSessionAction;
use App\Http\Requests\Storefront\UpdateCheckoutDraftRequest;
use App\Utilities\CartCookie;
use Illuminate\Http\JsonResponse;

final readonly class CheckoutDraftController
{
    public function update(
        UpdateCheckoutDraftRequest $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        UpdatePendingCheckoutSessionAction $updateSessionAction,
        RevalidateCartCouponAction $revalidateCartCoupon,
    ): JsonResponse {
        $user = $request->user();
        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());
        $input = $request->toDto();
        $customerEmail = $input->customerEmail ?? $user?->email;

        $updateSessionAction->handle(
            cartId: $cart->id,
            input: $input,
            customerEmail: $customerEmail,
            customerId: $user?->id,
            currencyCode: $request->attributes->get('active_currency'),
        );

        $couponRemoved = $revalidateCartCoupon->handle($cart, $customerEmail);

        return response()->json(['coupon_removed' => $couponRemoved]);
    }
}
