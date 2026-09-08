<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\CancelOrderAction;
use App\Exceptions\GatewayRefundFailedException;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Resources\Api\V1\Admin\AdminOrderStatusResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Validation\ValidationException;

final readonly class AdminCancelOrderController
{
    public function __invoke(
        CancelOrderRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        CancelOrderAction $action,
    ): AdminOrderStatusResource {
        try {
            $order = $action->handle($user, $order, $request->toDto());
        } catch (GatewayRefundFailedException $e) {
            throw ValidationException::withMessages(['gateway' => $e->getMessage()]);
        }

        return new AdminOrderStatusResource($order);
    }
}
