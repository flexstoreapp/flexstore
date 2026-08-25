<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Queries\OrderItemBreakdownQuery;
use App\StateMachines\FulfillmentStatusMachine;

final readonly class ReconcileFulfillmentStatusAction
{
    public function __construct(
        private TransitionFulfillmentStatusAction $transitionFulfillmentStatusAction,
        private OrderItemBreakdownQuery $itemBreakdownQuery,
    ) {
    }

    public function handle(Order $order): void
    {
        if ($order->canceled_at !== null) {
            return;
        }

        $order->unsetRelation('items');

        $itemBreakdown = $this->itemBreakdownQuery->execute($order);

        $order->loadMissing('items');

        $shippableItems = $order->items->where('requires_shipping', true);

        if ($shippableItems->isEmpty()) {
            return;
        }

        if (! $order->shipments()->exists()) {
            return;
        }

        $shippableItemIds = $shippableItems->pluck('id')->all();
        $shippableBreakdown = array_intersect_key($itemBreakdown, array_flip($shippableItemIds));

        $targetStatus = OrderItemBreakdownQuery::hasUnfulfilledItems($shippableBreakdown)
            ? FulfillmentStatus::InProgress
            : FulfillmentStatus::Fulfilled;

        if (FulfillmentStatusMachine::canTransition($order->fulfillment_status, $targetStatus)) {
            $this->transitionFulfillmentStatusAction->handle($order, $targetStatus);
        }
    }
}
