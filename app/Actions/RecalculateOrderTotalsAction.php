<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;
use App\Utilities\OrderUtility;

final readonly class RecalculateOrderTotalsAction
{
    public function __construct(
        private StoreOrderTaxAction $calculateAndStoreOrderTaxAction,
        private ReconcileOrderFinancialsAction $reconcileOrderFinancialsAction,
        private OrderUtility $orderUtility,
    ) {
    }

    public function handle(Order $order): void
    {
        $this->calculateAndStoreOrderTaxAction->handle($order);

        $total = $this->orderUtility->calculateOrderTotal(
            $order->subtotal,
            $order->tax_total,
            $order->shipping_total,
            $order->discount_total,
        );

        $order->update(['total' => $total]);

        $this->reconcileOrderFinancialsAction->handle($order);
    }
}
