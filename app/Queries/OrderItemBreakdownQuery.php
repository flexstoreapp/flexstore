<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderRefundItem;
use App\Models\OrderShipmentItem;
use Illuminate\Database\Eloquent\Builder;

final readonly class OrderItemBreakdownQuery
{
    /**
     * @param  array<int, array{unfulfilled: int, refunded: int, total_refunded: int}>  $breakdown
     */
    public static function hasUnfulfilledItems(array $breakdown): bool
    {
        return array_any($breakdown, fn (array $item): bool => $item['unfulfilled'] > 0);
    }

    /**
     * @return array<int, array{unfulfilled: int, refunded: int, total_refunded: int}>
     */
    public function execute(Order $order): array
    {
        $order->loadMissing('items');

        if ($order->items->isEmpty()) {
            return [];
        }

        $itemIds = $order->items->pluck('id');

        $shippedQuantities = OrderShipmentItem::query()
            ->whereIn('order_item_id', $itemIds)
            ->groupBy('order_item_id')
            ->selectRaw('order_item_id, sum(quantity) as shipped_quantity')
            ->pluck('shipped_quantity', 'order_item_id');

        $refundedQuantities = OrderRefundItem::query()
            ->whereIn('order_item_id', $itemIds)
            ->where('type', RefundItemType::Product)
            ->whereHas('refund', fn (Builder $q): Builder => $q->whereIn('status', [RefundStatus::Pending, RefundStatus::Completed]))
            ->groupBy('order_item_id')
            ->selectRaw('order_item_id, sum(quantity) as refunded_quantity')
            ->pluck('refunded_quantity', 'order_item_id');

        $result = [];

        foreach ($order->items as $item) {
            $shipped = (int) ($shippedQuantities[$item->id] ?? 0);
            $refunded = (int) ($refundedQuantities[$item->id] ?? 0);
            $unfulfilled = max(0, $item->quantity - $shipped - $refunded);
            $unshippedRefunded = max(0, min($refunded, $item->quantity - $shipped));

            if ($unfulfilled > 0 || $unshippedRefunded > 0 || $refunded > 0) {
                $result[$item->id] = [
                    'unfulfilled' => $unfulfilled,
                    'refunded' => $unshippedRefunded,
                    'total_refunded' => $refunded,
                ];
            }
        }

        return $result;
    }
}
