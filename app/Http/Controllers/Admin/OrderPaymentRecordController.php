<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\RecordPaymentAction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class OrderPaymentRecordController
{
    public function store(
        #[CurrentUser] User $user,
        Order $order,
        RecordPaymentAction $action,
    ): RedirectResponse {
        $action->handle($user, $order);

        return to_route('admin.orders.show', $order);
    }
}
