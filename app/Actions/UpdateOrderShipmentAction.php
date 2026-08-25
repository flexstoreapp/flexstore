<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateOrderShipmentInput;
use App\Enums\OrderActivityType;
use App\Exceptions\OrderConflictException;
use App\Models\OrderShipment;
use App\Models\User;
use App\Notifications\CustomerTrackingUpdatedNotification;
use Illuminate\Support\Facades\DB;

final readonly class UpdateOrderShipmentAction
{
    public function __construct(
        private StoreOrderActivityAction $storeOrderActivityAction,
        private SendCustomerNotificationAction $sendCustomerNotificationAction,
    ) {
    }

    public function handle(User $user, OrderShipment $shipment, UpdateOrderShipmentInput $input): OrderShipment
    {
        [$order, $trackingChanged] = DB::transaction(function () use ($user, $shipment, $input): array {
            $order = $shipment->order;

            if ($order->canceled_at !== null) {
                throw OrderConflictException::cannotUpdateShipmentForCanceledOrder();
            }

            $originalTrackingNumber = $shipment->tracking_number;
            $originalTrackingUrl = $shipment->tracking_url;

            $updates = [];
            if ($input->has('tracking_number')) {
                $updates['tracking_number'] = $input->trackingNumber;
            }
            if ($input->has('tracking_url')) {
                $updates['tracking_url'] = $input->trackingUrl;
            }

            if ($updates !== []) {
                $shipment->update($updates);
            }

            $trackingChanged = ($input->has('tracking_number') && $input->trackingNumber !== $originalTrackingNumber)
                || ($input->has('tracking_url') && $input->trackingUrl !== $originalTrackingUrl);

            if ($trackingChanged) {
                $this->storeOrderActivityAction->handle(
                    order: $order,
                    type: OrderActivityType::TrackingUpdated,
                    user: $user,
                    metadata: array_filter([
                        'shipment_id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                    ]),
                );
            }

            return [$order, $trackingChanged];
        });

        if ($trackingChanged && $input->notifyCustomer) {
            $this->sendCustomerNotificationAction->handle(
                new CustomerTrackingUpdatedNotification($order, $shipment),
                $order->customer_id ? $order->customer : null,
                $order->customer_email,
            );
        }

        return $shipment;
    }
}
