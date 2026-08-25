<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\CancelOrderAction;
use App\Exceptions\GatewayRefundFailedException;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class CancelOrderController
{
    public function __invoke(
        CancelOrderRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        CancelOrderAction $action,
    ): RedirectResponse {
        try {
            $action->handle($user, $order, $request->toDto());
        } catch (GatewayRefundFailedException $e) {
            return back()->withErrors(['gateway' => $e->getMessage()]);
        }

        return to_route('admin.orders.show', $order);
    }
}
