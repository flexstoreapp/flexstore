<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\VoidPaymentAction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class VoidPaymentController
{
    public function __invoke(
        #[CurrentUser] User $user,
        Order $order,
        VoidPaymentAction $action,
    ): RedirectResponse {
        $action->handle($order, $user);

        return to_route('admin.orders.show', $order);
    }
}
