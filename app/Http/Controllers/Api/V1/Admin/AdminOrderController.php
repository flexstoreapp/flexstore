<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\IndexAdminOrderRequest;
use App\Http\Resources\Api\V1\Admin\AdminOrderResource;
use App\Http\Resources\Api\V1\Admin\AdminOrderSummaryResource;
use App\Models\Order;
use App\Queries\OrderListQuery;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class AdminOrderController
{
    public function index(IndexAdminOrderRequest $request, OrderListQuery $query): AnonymousResourceCollection
    {
        return AdminOrderSummaryResource::collection(
            $query->execute($request->validated(), $request->safe()->integer('per_page', 15)),
        );
    }

    public function show(Order $order): AdminOrderResource
    {
        $order->load([
            'customer:id,name,email',
            'paymentGateway',
            'items.media',
            'items.product:id,url_handle,is_active',
            'taxDetails',
            'billingAddress',
            'shippingAddress',
            'activities' => fn (Relation $query): Relation => $query->latest('created_at')->latest('id'),
            'activities.user:id,name',
            'shipments' => fn (Relation $query): Relation => $query->latest(),
            'shipments.items.orderItem:id,product_title,variant_title,quantity',
            'refunds' => fn (Relation $query): Relation => $query->latest(),
        ]);

        return new AdminOrderResource($order);
    }
}
