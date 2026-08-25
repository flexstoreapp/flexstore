<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderRefundItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;

final readonly class RefundableOrderDataQuery
{
    /**
     * @return array{refundable_quantities: array<int, int>, refundable_shipping_amount: string, max_refundable_amount: string}
     */
    public function execute(Order $order): array
    {
        return [
            'refundable_quantities' => $this->getRefundableQuantities($order),
            'refundable_shipping_amount' => $this->getRefundableShippingAmount($order),
            'max_refundable_amount' => $order->net_paid_total,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function getRefundableQuantities(Order $order): array
    {
        $order->loadMissing('items');

        $refundedQuantities = OrderRefundItem::query()
            ->whereIn('order_item_id', $order->items->pluck('id'))
            ->whereHas('refund', fn (Builder $q): Builder => $q->whereIn('status', [RefundStatus::Pending, RefundStatus::Completed]))
            ->groupBy('order_item_id')
            ->selectRaw('order_item_id, sum(quantity) as refunded_quantity')
            ->pluck('refunded_quantity', 'order_item_id');

        $result = [];

        foreach ($order->items as $item) {
            $result[$item->id] = $item->quantity - (int) ($refundedQuantities[$item->id] ?? 0);
        }

        return $result;
    }

    private function getRefundableShippingAmount(Order $order): string
    {
        $previousShippingRefunds = OrderRefundItem::query()
            ->where('type', RefundItemType::Shipping)
            ->whereHas('refund', fn (Builder $q): Builder => $q
                ->where('order_id', $order->id)
                ->whereIn('status', [RefundStatus::Pending, RefundStatus::Completed]))
            ->sum('amount');

        $remainingShipping = BigDecimal::of($order->shipping_total)->minus((string) $previousShippingRefunds);

        if ($remainingShipping->isNegativeOrZero()) {
            return '0.0000';
        }

        return $remainingShipping->toScale(4, RoundingMode::HalfUp)->toString();
    }
}
