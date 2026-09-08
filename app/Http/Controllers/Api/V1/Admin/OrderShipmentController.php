<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\DeleteOrderShipmentAction;
use App\Actions\StoreOrderShipmentAction;
use App\Actions\UpdateOrderShipmentAction;
use App\Http\Requests\Admin\StoreOrderShipmentRequest;
use App\Http\Requests\Admin\UpdateOrderShipmentRequest;
use App\Http\Resources\Api\V1\Admin\OrderShipmentResource;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

final readonly class OrderShipmentController
{
    public function store(
        StoreOrderShipmentRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        StoreOrderShipmentAction $action,
    ): OrderShipmentResource {
        $shipment = $action->handle($user, $order, $request->toDto());

        return new OrderShipmentResource($shipment->load('carrier', 'items.orderItem'));
    }

    public function update(
        UpdateOrderShipmentRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        OrderShipment $shipment,
        UpdateOrderShipmentAction $action,
    ): OrderShipmentResource {
        $shipment = $action->handle($user, $shipment, $request->toDto());

        return new OrderShipmentResource($shipment->load('carrier', 'items.orderItem'));
    }

    public function destroy(
        #[CurrentUser] User $user,
        Order $order,
        OrderShipment $shipment,
        DeleteOrderShipmentAction $action,
    ): Response {
        $action->handle($user, $shipment);

        return response()->noContent();
    }
}
