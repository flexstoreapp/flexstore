<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ApplyCheckoutOptionsAction;
use App\Actions\ResolveVisitorCartAction;
use App\Http\Requests\Storefront\StoreCheckoutOptionRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Queries\StorefrontCartQuery;
use App\Utilities\ApiCartToken;

final readonly class CheckoutOptionController
{
    public function store(
        StoreCheckoutOptionRequest $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        ApplyCheckoutOptionsAction $applyCheckoutOptions,
        StorefrontCartQuery $query,
    ): CartResource {
        $cart = $resolveVisitorCart->handle(ApiCartToken::from($request), $request->user());

        $applyCheckoutOptions->handle(
            cart: $cart,
            input: $request->toDto(),
            user: $request->user(),
            currencyCode: $request->attributes->get('active_currency'),
        );

        return new CartResource($query->execute($cart->refresh()));
    }
}
