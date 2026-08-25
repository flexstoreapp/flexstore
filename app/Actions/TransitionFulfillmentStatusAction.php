<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\FulfillmentTransitionResult;
use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\StateMachines\FulfillmentStatusMachine;
use Illuminate\Support\Facades\DB;

final readonly class TransitionFulfillmentStatusAction
{
    public function __construct(
        private RecalculateCustomerLifetimeValueAction $recalculateCustomerLifetimeValueAction,
    ) {
    }

    public function handle(
        Order $order,
        FulfillmentStatus $to,
    ): FulfillmentTransitionResult {
        return DB::transaction(function () use ($order, $to): FulfillmentTransitionResult {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->fulfillment_status;

            if ($from === $to) {
                return new FulfillmentTransitionResult($order, $from);
            }

            FulfillmentStatusMachine::assertCanTransition($from, $to);

            $order->update(['fulfillment_status' => $to]);

            if ($to === FulfillmentStatus::Fulfilled) {
                $this->recalculateCustomerLifetimeValueAction->handle($order->customer);
            }

            return new FulfillmentTransitionResult($order, $from);
        });
    }
}
