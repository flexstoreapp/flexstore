<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\StoreOrderActivityAction;
use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Http\Resources\Api\V1\Admin\AdminOrderStatusResource;
use App\Models\Order;
use App\Models\User;
use App\Queries\OrderItemBreakdownQuery;
use Illuminate\Container\Attributes\CurrentUser;

final readonly class AdminHoldOrderController
{
    public function __invoke(
        #[CurrentUser] User $user,
        Order $order,
        TransitionFulfillmentStatusAction $action,
        StoreOrderActivityAction $storeOrderActivityAction,
        OrderItemBreakdownQuery $itemBreakdownQuery,
    ): AdminOrderStatusResource {
        abort_if($order->is_canceled, 409, __('This order has been canceled.'));

        abort_unless(
            in_array($order->fulfillment_status, [FulfillmentStatus::Unfulfilled, FulfillmentStatus::InProgress], true),
            409,
            __('This order cannot be put on hold.'),
        );

        abort_if(! OrderItemBreakdownQuery::hasUnfulfilledItems($itemBreakdownQuery->execute($order)), 409, __('This order has nothing left to fulfill.'));

        $result = $action->handle($order, FulfillmentStatus::OnHold);

        $storeOrderActivityAction->handle(
            order: $result->order,
            type: OrderActivityType::FulfillmentStatusChanged,
            user: $user,
            metadata: [
                'from_status' => $result->from->value,
                'to_status' => $result->order->fulfillment_status->value,
            ],
        );

        return new AdminOrderStatusResource($result->order);
    }
}
