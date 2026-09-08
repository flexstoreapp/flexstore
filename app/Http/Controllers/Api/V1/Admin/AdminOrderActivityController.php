<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\StoreOrderActivityAction;
use App\Enums\OrderActivityType;
use App\Http\Requests\Admin\StoreOrderActivityRequest;
use App\Http\Requests\Admin\UpdateOrderActivityRequest;
use App\Http\Resources\Api\V1\Admin\AdminOrderActivityResource;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final readonly class AdminOrderActivityController
{
    public function index(Order $order): AnonymousResourceCollection
    {
        $activities = $order->activities()
            ->with('user:id,name')
            ->latest('created_at')
            ->latest('id')
            ->get();

        return AdminOrderActivityResource::collection($activities);
    }

    public function store(
        StoreOrderActivityRequest $request,
        Order $order,
        #[CurrentUser] User $user,
        StoreOrderActivityAction $action,
    ): AdminOrderActivityResource {
        $activity = $action->handle(
            order: $order,
            type: OrderActivityType::NoteAdded,
            user: $user,
            comment: $request->safe()->string('comment')->value(),
        );

        $activity->setRelation('user', $user);

        return new AdminOrderActivityResource($activity);
    }

    public function update(
        UpdateOrderActivityRequest $request,
        Order $order,
        OrderActivity $activity,
    ): AdminOrderActivityResource {
        $this->assertEditableNote($order, $activity);

        $activity->update([
            'comment' => $request->safe()->string('comment')->value(),
        ]);

        $activity->load('user:id,name');

        return new AdminOrderActivityResource($activity);
    }

    public function destroy(Order $order, OrderActivity $activity): Response
    {
        $this->assertEditableNote($order, $activity);

        $activity->delete();

        return response()->noContent();
    }

    private function assertEditableNote(Order $order, OrderActivity $activity): void
    {
        abort_unless($activity->order_id === $order->id, 403);
        abort_unless($activity->type === OrderActivityType::NoteAdded, 403);
    }
}
