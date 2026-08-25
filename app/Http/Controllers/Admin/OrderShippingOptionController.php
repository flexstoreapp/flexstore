<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use App\Http\Requests\Admin\OrderShippingOptionsRequest;
use App\Queries\EligibleShippingOptionsQuery;
use App\Utilities\OrderUtility;
use Illuminate\Http\JsonResponse;

final readonly class OrderShippingOptionController
{
    public function __invoke(
        OrderShippingOptionsRequest $request,
        OrderUtility $orderUtility,
        EligibleShippingOptionsQuery $query
    ): JsonResponse {
        $hydratedItems = $orderUtility->hydrateItems($request->validated('items'));
        $orderItemsSummary = OrderItemsSummary::fromHydratedItems($hydratedItems);

        $shippingAddress = $request->validated('shipping_address');
        $address = is_array($shippingAddress) ? AddressLocation::fromArray($shippingAddress) : null;

        $shippingOptions = $query->execute($orderItemsSummary, $address)->values();

        return response()->json($shippingOptions);
    }
}
