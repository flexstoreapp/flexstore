<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Order;
use App\Models\OrderShipmentItem;
use Illuminate\Database\Eloquent\Builder;

final readonly class OrderShippedQuantitiesQuery
{
    /**
     * @return array<int, int>
     */
    public function execute(Order $order): array
    {
        return OrderShipmentItem::query()
            ->whereHas('orderItem', fn (Builder $query): Builder => $query->where('order_id', $order->id))
            ->groupBy('order_item_id')
            ->selectRaw('order_item_id, sum(quantity) as shipped_quantity')
            ->pluck('shipped_quantity', 'order_item_id')
            ->map(fn (mixed $quantity): int => (int) $quantity)
            ->all();
    }
}
