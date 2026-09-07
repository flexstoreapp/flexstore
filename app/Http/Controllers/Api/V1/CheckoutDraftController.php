<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResolveVisitorCartAction;
use App\Actions\RevalidateCartCouponAction;
use App\Actions\UpdatePendingCheckoutSessionAction;
use App\Http\Requests\Storefront\UpdateCheckoutDraftRequest;
use App\Utilities\ApiCartToken;
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
        $cart = $resolveVisitorCart->handle(ApiCartToken::from($request), $user);
        $input = $request->toDto();
        $customerEmail = $input->customerEmail ?? $user?->email;

        $updateSessionAction->handle(
            cartId: $cart->id,
            input: $input,
            customerEmail: $customerEmail,
            customerId: $user?->id,
            currencyCode: $request->attributes->get('active_currency'),
        );

        return response()->json([
            'data' => [
                'cart_token' => $cart->id,
                'coupon_removed' => $revalidateCartCoupon->handle($cart, $customerEmail),
            ],
        ]);
    }
}
