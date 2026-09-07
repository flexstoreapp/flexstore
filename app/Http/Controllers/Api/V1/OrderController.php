<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Storefront\ShowAccountOrdersRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\OrderSummaryResource;
use App\Models\User;
use App\Queries\CustomerOrderListQuery;
use App\Queries\CustomerOrderQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class OrderController
{
    public function index(
        ShowAccountOrdersRequest $request,
        #[CurrentUser] User $user,
        CustomerOrderListQuery $query,
    ): AnonymousResourceCollection {
        $status = $request->safe()->string('status')->value();

        return OrderSummaryResource::collection($query->execute($user, $status !== '' ? $status : null));
    }

    public function show(int $orderId, #[CurrentUser] User $user, CustomerOrderQuery $query): OrderResource
    {
        return new OrderResource($query->execute($orderId, $user, withLineDetail: false));
    }
}
