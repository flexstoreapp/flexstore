<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOrderActivityAction;
use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class ReleaseOrderHoldController
{
    public function __invoke(
        #[CurrentUser] User $user,
        Order $order,
        TransitionFulfillmentStatusAction $action,
        StoreOrderActivityAction $storeOrderActivityAction,
    ): RedirectResponse {
        abort_if($order->is_canceled, 409);

        abort_unless($order->fulfillment_status === FulfillmentStatus::OnHold, 409);

        $hasShipments = $order->shipments()->exists();

        $targetStatus = $hasShipments
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

        return to_route('admin.orders.show', $order);
    }
}
