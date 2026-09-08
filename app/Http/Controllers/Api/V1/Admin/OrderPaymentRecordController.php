<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\RecordPaymentAction;
use App\Http\Resources\Api\V1\Admin\OrderPaymentSummaryResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final readonly class OrderPaymentRecordController
{
    public function store(
        #[CurrentUser] User $user,
        Order $order,
        RecordPaymentAction $action,
    ): OrderPaymentSummaryResource {
        $action->handle($user, $order);

        return new OrderPaymentSummaryResource($order->refresh());
    }
}
