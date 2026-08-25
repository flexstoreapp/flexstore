<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ShipmentItemInput;
use App\DTOs\StoreOrderShipmentInput;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Exceptions\OrderConflictException;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\User;
use App\Notifications\CustomerOrderFulfilledNotification;
use Illuminate\Support\Facades\DB;

final readonly class StoreOrderShipmentAction
{
    public function __construct(
        private ReconcileFulfillmentStatusAction $reconcileFulfillmentStatusAction,
        private StoreOrderActivityAction $storeOrderActivityAction,
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
    ) {
    }

    public function handle(User $user, Order $order, StoreOrderShipmentInput $input): OrderShipment
    {
        $shipment = $this->createBaseShipment($user, $order, $input);

        $order = $this->finalizeShipment($user, $order, $input, $shipment);

        if ($input->notifyCustomer) {
            $this->sendCustomerNotificationAction->handle(
                new CustomerOrderFulfilledNotification($order, $shipment),
                $order->customer_id ? $order->customer : null,
                $order->customer_email,
            );
        }

        return $shipment;
    }

    private function createBaseShipment(User $user, Order $order, StoreOrderShipmentInput $input): OrderShipment
    {
        return DB::transaction(function () use ($user, $order, $input): OrderShipment {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->canceled_at !== null || $order->fulfillment_status === FulfillmentStatus::OnHold) {
                throw OrderConflictException::cannotCreateShipmentForClosedOrder();
            }

            $shipment = OrderShipment::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'shipping_carrier_id' => null,
                'tracking_number' => $input->trackingNumber,
                'tracking_url' => $input->trackingUrl,
                'shipped_at' => now(),
            ]);

            $now = now();
            $shipmentItems = [];

            foreach ($input->items as $item) {
                $shipmentItems[] = [
                    'order_shipment_id' => $shipment->id,
                    'order_item_id' => $item->orderItemId,
                    'quantity' => $item->quantity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            OrderShipmentItem::query()->insert($shipmentItems);

            return $shipment;
        });
    }

    private function finalizeShipment(
        User $user,
        Order $order,
        StoreOrderShipmentInput $input,
        OrderShipment $shipment,
    ): Order {
        return DB::transaction(function () use ($user, $order, $input, $shipment): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $order->loadMissing('items');

            $orderItemsById = $order->items->keyBy('id');

            $this->storeOrderActivityAction->handle(
                order: $order,
                type: OrderActivityType::ItemsFulfilled,
                user: $user,
                metadata: array_filter([
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'items' => array_map(function (ShipmentItemInput $item) use ($orderItemsById): array {
                        $orderItem = $orderItemsById->get($item->orderItemId);

                        return array_filter([
                            'title' => $orderItem?->getTranslations('product_title'),
                            'variant' => $orderItem?->variant_title,
                            'quantity' => $item->quantity,
                        ]);
                    }, $input->items),
                ]),
            );

            $this->reconcileFulfillmentStatusAction->handle($order);

            return $order;
        });
    }
}
