<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOrderActivityAction;
use App\Enums\OrderActivityType;
use App\Http\Requests\Admin\StoreOrderActivityRequest;
use App\Http\Requests\Admin\UpdateOrderActivityRequest;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class OrderActivityController
{
    public function store(
        StoreOrderActivityRequest $request,
        Order $order,
        #[CurrentUser] User $user,
        StoreOrderActivityAction $action,
    ): RedirectResponse {
        $action->handle(
            order: $order,
            type: OrderActivityType::NoteAdded,
            user: $user,
            comment: $request->validated('comment'),
        );

        return back();
    }

    public function update(
        UpdateOrderActivityRequest $request,
        Order $order,
        OrderActivity $activity,
    ): RedirectResponse {
        abort_unless($activity->order_id === $order->id, 403);
        abort_unless($activity->type === OrderActivityType::NoteAdded, 403);

        $activity->update([
            'comment' => $request->validated('comment'),
        ]);

        return back();
    }

    public function destroy(
        Order $order,
        OrderActivity $activity,
    ): RedirectResponse {
        abort_unless($activity->order_id === $order->id, 403);
        abort_unless($activity->type === OrderActivityType::NoteAdded, 403);

        $activity->delete();

        return back();
    }
}
