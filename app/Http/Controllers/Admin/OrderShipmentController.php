<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteOrderShipmentAction;
use App\Actions\StoreOrderShipmentAction;
use App\Actions\UpdateOrderShipmentAction;
use App\Http\Requests\Admin\StoreOrderShipmentRequest;
use App\Http\Requests\Admin\UpdateOrderShipmentRequest;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class OrderShipmentController
{
    public function store(
        StoreOrderShipmentRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        StoreOrderShipmentAction $action,
    ): RedirectResponse {
        $action->handle($user, $order, $request->toDto());

        return back();
    }

    public function update(
        UpdateOrderShipmentRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        OrderShipment $shipment,
        UpdateOrderShipmentAction $action,
    ): RedirectResponse {
        $action->handle($user, $shipment, $request->toDto());

        return back();
    }

    public function destroy(
        #[CurrentUser] User $user,
        Order $order,
        OrderShipment $shipment,
        DeleteOrderShipmentAction $action,
    ): RedirectResponse {
        $action->handle($user, $shipment);

        return back();
    }
}
