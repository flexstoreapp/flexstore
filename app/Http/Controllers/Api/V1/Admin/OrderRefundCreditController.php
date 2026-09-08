<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\RefundOrderCreditAction;
use App\Http\Resources\Api\V1\Admin\OrderRefundResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final readonly class OrderRefundCreditController
{
    public function store(
        #[CurrentUser] User $user,
        Order $order,
        RefundOrderCreditAction $action,
    ): OrderRefundResource {
        $refund = $action->handle($user, $order);

        return new OrderRefundResource($refund->load('items'));
    }
}
