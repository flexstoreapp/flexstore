<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ResendOrderNotificationAction;
use App\Enums\OrderEmailType;
use App\Http\Requests\Admin\ResendOrderNotificationRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

final readonly class AdminResendOrderNotificationController
{
    public function __invoke(
        ResendOrderNotificationRequest $request,
        Order $order,
        #[CurrentUser] User $user,
        ResendOrderNotificationAction $action,
    ): Response {
        $type = OrderEmailType::from($request->safe()->string('type')->value());

        $shipment = $request->safe()->has('shipment_id')
            ? $order->shipments()->findOrFail($request->safe()->integer('shipment_id'))
            : null;

        $refund = $request->safe()->has('refund_id')
            ? $order->refunds()->findOrFail($request->safe()->integer('refund_id'))
            : null;

        $action->handle($user, $order, $type, $shipment, $refund);

        return response()->noContent();
    }
}
