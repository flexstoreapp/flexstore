<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOrderActivityAction;
use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Models\Order;
use App\Models\User;
use App\Queries\OrderItemBreakdownQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class HoldOrderController
{
    public function __invoke(
        #[CurrentUser] User $user,
        Order $order,
        TransitionFulfillmentStatusAction $action,
        StoreOrderActivityAction $storeOrderActivityAction,
        OrderItemBreakdownQuery $itemBreakdownQuery,
    ): RedirectResponse {
        abort_if($order->is_canceled, 409);

        abort_unless(
            in_array($order->fulfillment_status, [FulfillmentStatus::Unfulfilled, FulfillmentStatus::InProgress], true),
            409,
        );

        abort_if(! OrderItemBreakdownQuery::hasUnfulfilledItems($itemBreakdownQuery->execute($order)), 409);

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

        return to_route('admin.orders.show', $order);
    }
}
