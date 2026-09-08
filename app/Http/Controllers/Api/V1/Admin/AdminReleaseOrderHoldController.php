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
use Illuminate\Container\Attributes\CurrentUser;

final readonly class AdminReleaseOrderHoldController
{
    public function __invoke(
        #[CurrentUser] User $user,
        Order $order,
        TransitionFulfillmentStatusAction $action,
        StoreOrderActivityAction $storeOrderActivityAction,
    ): AdminOrderStatusResource {
        abort_if($order->is_canceled, 409, __('This order has been canceled.'));

        abort_unless($order->fulfillment_status === FulfillmentStatus::OnHold, 409, __('This order is not on hold.'));

        $targetStatus = $order->shipments()->exists()
            ? FulfillmentStatus::InProgress
            : FulfillmentStatus::Unfulfilled;

        $result = $action->handle($order, $targetStatus);

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
