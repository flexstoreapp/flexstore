<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\VoidPaymentAction;
use App\Http\Resources\Api\V1\Admin\OrderPaymentSummaryResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final readonly class VoidPaymentController
{
    public function __invoke(
        #[CurrentUser] User $user,
        Order $order,
        VoidPaymentAction $action,
    ): OrderPaymentSummaryResource {
        $action->handle($order, $user);

        return new OrderPaymentSummaryResource($order->refresh());
    }
}
