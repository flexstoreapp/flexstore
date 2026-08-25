<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Exceptions\OrderConflictException;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\User;
use App\Queries\OrderItemBreakdownQuery;
use App\StateMachines\FulfillmentStatusMachine;
use Illuminate\Support\Facades\DB;

final readonly class DeleteOrderShipmentAction
{
    public function __construct(
        private StoreOrderActivityAction $storeOrderActivityAction,
        private TransitionFulfillmentStatusAction $transitionFulfillmentStatusAction,
        private OrderItemBreakdownQuery $itemBreakdownQuery,
    ) {
    }

    public function handle(User $user, OrderShipment $shipment): void
    {
        DB::transaction(function () use ($user, $shipment): void {
            $order = $shipment->order;

            if ($order->canceled_at !== null) {
                throw OrderConflictException::cannotCancelFulfillmentForCanceledOrder();
            }

            $shipment->loadMissing('items.orderItem', 'carrier');

            $items = $shipment->items->map(fn (OrderShipmentItem $shipmentItem): array => array_filter([
                'title' => $shipmentItem->orderItem->getTranslations('product_title'),
                'variant' => $shipmentItem->orderItem->variant_title,
                'quantity' => $shipmentItem->quantity,
            ]))->all();

            $shipment->items()->delete();
            $shipment->delete();

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::FulfillmentCanceled,
                user: $user,
                metadata: ['items' => $items],
            );

            $this->recalculateFulfillmentStatus($order);
        });
    }

    private function recalculateFulfillmentStatus(Order $order): void
    {
        if (! in_array($order->fulfillment_status, [FulfillmentStatus::InProgress, FulfillmentStatus::Fulfilled], true)) {
            return;
        }

        $order->unsetRelation('items');

        $itemBreakdown = $this->itemBreakdownQuery->execute($order);

        $order->loadMissing('items');

        $shippableItems = $order->items->where('requires_shipping', true);

        if ($shippableItems->isEmpty()) {
            return;
        }

        $shippableItemIds = $shippableItems->pluck('id')->all();
        $shippableBreakdown = array_intersect_key($itemBreakdown, array_flip($shippableItemIds));

        $totalShippable = (int) $shippableItems->sum('quantity');
        $totalRemaining = array_sum(array_column($shippableBreakdown, 'unfulfilled'));
        $hasShipments = $order->shipments()->exists();

        if ($totalRemaining === $totalShippable || (! $hasShipments && $totalRemaining === 0)) {
            $targetStatus = FulfillmentStatus::Unfulfilled;
        } elseif ($totalRemaining > 0) {
            $targetStatus = FulfillmentStatus::InProgress;
        } else {
            return;
        }

        if ($order->fulfillment_status !== $targetStatus && FulfillmentStatusMachine::canTransition($order->fulfillment_status, $targetStatus)) {
            $this->transitionFulfillmentStatusAction->handle(
                order: $order,
                to: $targetStatus,
            );
        }
    }
}
