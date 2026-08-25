<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ResolveVisitorCartAction;
use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use App\Http\Requests\Storefront\CheckoutPaymentOptionsRequest;
use App\Models\CartItem;
use App\Queries\EligiblePaymentOptionsQuery;
use App\Utilities\CartCookie;
use App\Utilities\OrderUtility;
use Illuminate\Http\JsonResponse;

final readonly class CheckoutPaymentOptionController
{
    public function index(
        CheckoutPaymentOptionsRequest $request,
        OrderUtility $orderUtility,
        ResolveVisitorCartAction $resolveVisitorCart,
        EligiblePaymentOptionsQuery $paymentOptionsQuery,
    ): JsonResponse {
        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        $hydratedItems = $orderUtility->hydrateItems(
            $cart->items->map(fn (CartItem $item): array => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ])->all(),
        );

        $orderItemsSummary = OrderItemsSummary::fromHydratedItems($hydratedItems);

        $addressArr = $request->safe()->array('address');
        $address = $addressArr !== [] && ! empty($addressArr['country_code'])
            ? AddressLocation::fromArray($addressArr)
            : null;

        return response()->json([
            'payment' => $paymentOptionsQuery->execute(
                $orderItemsSummary,
                $address,
                $request->attributes->get('active_currency'),
            ),
        ]);
    }
}
