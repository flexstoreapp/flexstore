<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\OrderRefundItemInput;
use App\DTOs\StoreOrderRefundInput;
use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use App\Exceptions\OrderNotRefundableException;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use App\Utilities\RefundCalculator;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

final readonly class StoreOrderRefundAction
{
    public function __construct(
        private RefundCalculator $calculator,
    ) {
    }

    public function handle(Order $order, StoreOrderRefundInput $input): OrderRefund
    {
        return DB::transaction(function () use ($order, $input): OrderRefund {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $order->load('items.product', 'items.productVariant');

            if (! $order->is_refundable) {
                throw new OrderNotRefundableException();
            }

            $itemQuantities = [];
            foreach ($input->items as $item) {
                $itemQuantities[$item->orderItemId] = $item->quantity;
            }

            $scale = Currency::getDecimalPlaces($order->currency_code);
            $totals = $this->calculator->calculate($order, $itemQuantities);

            $productTotal = BigDecimal::of($totals->productTotal);
            $taxTotal = BigDecimal::of($totals->taxTotal);
            $discountTotal = BigDecimal::of($totals->discountTotal);

            $orderItems = $this->getOrderItemsForRefund($order, $input->items);
            $productRows = [];

            foreach ($input->items as $item) {
                $orderItem = $orderItems[$item->orderItemId];
                $itemAmount = BigDecimal::of($orderItem->unit_price)->multipliedBy($item->quantity);

                $productRows[] = [
                    'orderItem' => $orderItem,
                    'quantity' => $item->quantity,
                    'amount' => $itemAmount->toScale($scale, RoundingMode::HalfUp)->toString(),
                    'restock' => $input->restock,
                ];
            }

            $refundBase = $productTotal
                ->minus($discountTotal)
                ->plus($taxTotal)
                ->plus($input->shippingAmount)
                ->toScale($scale, RoundingMode::HalfUp);

            $restockingFee = BigDecimal::of($input->restockingFee)->toScale($scale, RoundingMode::HalfUp);

            if ($restockingFee->isGreaterThan($refundBase)) {
                $restockingFee = $refundBase->isNegative()
                    ? BigDecimal::zero()->toScale($scale, RoundingMode::HalfUp)
                    : $refundBase;
            }

            $computedTotal = $refundBase->minus($restockingFee)->toScale($scale, RoundingMode::HalfUp);

            if ($computedTotal->isNegative()) {
                $computedTotal = BigDecimal::zero()->toScale($scale, RoundingMode::HalfUp);
            }

            $refundAmount = $input->isManualTotal && $input->total !== null
                ? BigDecimal::of($input->total)->toScale($scale, RoundingMode::HalfUp)->toString()
                : $computedTotal->toString();

            $refund = $this->createRefund($order, $refundAmount, $input->isManualTotal, $input->reason);

            if ($productRows !== []) {
                $this->createProductRefundItems($refund, $productRows);
            }

            if ($taxTotal->isGreaterThan(0)) {
                $this->createRefundItem($refund, RefundItemType::Tax, $taxTotal->toString());
            }

            if ($discountTotal->isGreaterThan(0)) {
                $this->createRefundItem($refund, RefundItemType::Discount, $discountTotal->toString());
            }

            if (BigDecimal::of($input->shippingAmount)->isGreaterThan(0)) {
                $this->createRefundItem($refund, RefundItemType::Shipping, $input->shippingAmount);
            }

            if ($restockingFee->isGreaterThan(0)) {
                $this->createRefundItem(
                    $refund,
                    RefundItemType::RestockingFee,
                    $restockingFee->toScale($scale, RoundingMode::HalfUp)->toString(),
                );
            }

            if ($input->isManualTotal) {
                $adjustment = BigDecimal::of($refundAmount)->minus($computedTotal)->toScale($scale, RoundingMode::HalfUp);

                if (! $adjustment->isZero()) {
                    $this->createRefundItem($refund, RefundItemType::Adjustment, $adjustment->toString());
                }
            }

            return $refund;
        });
    }

    /**
     * @param  list<OrderRefundItemInput>  $items
     * @return array<int, OrderItem>
     */
    private function getOrderItemsForRefund(Order $order, array $items): array
    {
        if ($items === []) {
            return [];
        }

        $orderItemIds = array_map(fn (OrderRefundItemInput $i): int => $i->orderItemId, $items);

        return $order->items
            ->whereIn('id', $orderItemIds)
            ->keyBy('id')
            ->all();
    }

    private function createRefund(Order $order, string $refundAmount, bool $isOverridden, ?string $reason): OrderRefund
    {
        return OrderRefund::query()->create([
            'order_id' => $order->id,
            'status' => RefundStatus::Pending,
            'amount' => $refundAmount,
            'is_manual_total' => $isOverridden,
            'reason' => $reason,
        ]);
    }

    /**
     * @param  list<array{orderItem: OrderItem, quantity: int, amount: string, restock: bool}>  $productRows
     */
    private function createProductRefundItems(OrderRefund $refund, array $productRows): void
    {
        $now = now();
        $refundItemsData = [];

        foreach ($productRows as $row) {
            $refundItemsData[] = [
                'order_refund_id' => $refund->id,
                'type' => RefundItemType::Product,
                'order_item_id' => $row['orderItem']->id,
                'quantity' => $row['quantity'],
                'amount' => $row['amount'],
                'restock' => $row['restock'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        OrderRefundItem::query()->insert($refundItemsData);
    }

    private function createRefundItem(OrderRefund $refund, RefundItemType $type, string $amount): void
    {
        OrderRefundItem::query()->create([
            'order_refund_id' => $refund->id,
            'type' => $type,
            'order_item_id' => null,
            'quantity' => null,
            'amount' => $amount,
            'restock' => false,
        ]);
    }
}
